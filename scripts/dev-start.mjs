#!/usr/bin/env node
import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { readdirSync, statSync } from 'node:fs';
import { join, relative } from 'node:path';

const SITE_URL = 'http://localhost:8888';
const ADMIN_URL = `${SITE_URL}/wp-admin`;
const NETWORK = 'wpmlf-net';
const DB_CONTAINER = 'wpmlf-db';
const WP_CONTAINER = 'wpmlf-wordpress';
const DB_VOLUME = 'wpmlf-db-data';
const WP_VOLUME = 'wpmlf-wp-data';
const DB_IMAGE = 'mariadb:11.4';
const WP_IMAGE = 'wordpress:6.5-php8.2-apache';
const WPCLI_IMAGE = 'wordpress:cli-php8.2';
const SYNC_HASH_FILE = '/var/www/html/wp-content/uploads/mailto-link-form-sync-hash.txt';
const PLUGIN_SLUG = 'mailto-link-form';
const SYNC_EXCLUDES = new Set([
  '.git',
  '.gitignore',
  '.wp-env.json',
  'node_modules',
  '.npm-cache',
  'dist',
  'scripts',
  'package.json',
  'package-lock.json',
  'docs-wordpress-org-submission.md',
  'docker-data',
]);

function run(command, args, options = {}) {
  const result = spawnSync(command, args, {
    stdio: options.capture ? 'pipe' : 'inherit',
    encoding: 'utf8',
  });

  if (options.capture) {
    return {
      status: result.status ?? 1,
      stdout: (result.stdout || '').trim(),
      stderr: (result.stderr || '').trim(),
    };
  }

  return result.status ?? 1;
}

function existsContainer(name) {
  return run('docker', ['container', 'inspect', name], { capture: true }).status === 0;
}

function ensureNetwork(name) {
  if (run('docker', ['network', 'inspect', name], { capture: true }).status !== 0) {
    return run('docker', ['network', 'create', name]) === 0;
  }
  return true;
}

function ensureVolume(name) {
  if (run('docker', ['volume', 'inspect', name], { capture: true }).status !== 0) {
    return run('docker', ['volume', 'create', name]) === 0;
  }
  return true;
}

function startDb() {
  if (existsContainer(DB_CONTAINER)) {
    return run('docker', ['start', DB_CONTAINER]) === 0;
  }

  return run('docker', [
    'run',
    '-d',
    '--name',
    DB_CONTAINER,
    '--network',
    NETWORK,
    '--health-cmd',
    'healthcheck.sh --connect --innodb_initialized',
    '--health-interval',
    '5s',
    '--health-timeout',
    '3s',
    '--health-retries',
    '20',
    '-e',
    'MARIADB_DATABASE=wordpress',
    '-e',
    'MARIADB_USER=wordpress',
    '-e',
    'MARIADB_PASSWORD=wordpress',
    '-e',
    'MARIADB_ROOT_PASSWORD=root',
    '-v',
    `${DB_VOLUME}:/var/lib/mysql`,
    DB_IMAGE,
  ]) === 0;
}

function startWordPress() {
  if (existsContainer(WP_CONTAINER)) {
    return run('docker', ['start', WP_CONTAINER]) === 0;
  }

  return run('docker', [
    'run',
    '-d',
    '--name',
    WP_CONTAINER,
    '--network',
    NETWORK,
    '-p',
    '8888:80',
    '-e',
    'WORDPRESS_DB_HOST=wpmlf-db:3306',
    '-e',
    'WORDPRESS_DB_USER=wordpress',
    '-e',
    'WORDPRESS_DB_PASSWORD=wordpress',
    '-e',
    'WORDPRESS_DB_NAME=wordpress',
    '-v',
    `${WP_VOLUME}:/var/www/html`,
    WP_IMAGE,
  ]) === 0;
}

function runWpCli(args, capture = false) {
  return run('docker', [
    'run',
    '--rm',
    '--network',
    NETWORK,
    '-e',
    'WORDPRESS_DB_HOST=wpmlf-db:3306',
    '-e',
    'WORDPRESS_DB_USER=wordpress',
    '-e',
    'WORDPRESS_DB_PASSWORD=wordpress',
    '-e',
    'WORDPRESS_DB_NAME=wordpress',
    '-v',
    `${WP_VOLUME}:/var/www/html`,
    WPCLI_IMAGE,
    'wp',
    ...args,
  ], { capture });
}

function walkFiles(root, current = root, files = []) {
  const entries = readdirSync(current, { withFileTypes: true });
  for (const entry of entries) {
    const full = join(current, entry.name);
    const rel = relative(root, full).replaceAll('\\', '/');
    const top = rel.split('/')[0];

    if (SYNC_EXCLUDES.has(top)) {
      continue;
    }

    if (entry.isDirectory()) {
      walkFiles(root, full, files);
      continue;
    }

    if (entry.isFile()) {
      if (entry.name === '.gitkeep') {
        continue;
      }
      const stats = statSync(full);
      files.push(`${rel}:${stats.size}:${stats.mtimeMs}`);
    }
  }
  return files;
}

function buildPluginHash() {
  const hash = createHash('sha256');
  const metadata = walkFiles(process.cwd()).sort();
  for (const line of metadata) {
    hash.update(line);
    hash.update('\n');
  }
  return hash.digest('hex');
}

function getRemotePluginHash() {
  const res = run('docker', ['exec', WP_CONTAINER, 'sh', '-lc', `cat ${SYNC_HASH_FILE}`], { capture: true });
  return res.status === 0 ? res.stdout : '';
}

function buildTarArgs() {
  return [
    '-C',
    process.cwd(),
    '--exclude=.git',
    '--exclude=node_modules',
    '--exclude=.npm-cache',
    '--exclude=.gitignore',
    '--exclude=.wp-env.json',
    '--exclude=dist',
    '--exclude=scripts',
    '--exclude=package.json',
    '--exclude=package-lock.json',
    '--exclude=docs-wordpress-org-submission.md',
    '--exclude=.gitkeep',
    '--exclude=docker-data',
    '-cf',
    '-',
    '.',
  ];
}

function syncPluginFiles() {
  const pluginDir = `/var/www/html/wp-content/plugins/${PLUGIN_SLUG}`;
  const syncHashDir = '/var/www/html/wp-content/uploads';
  const localHash = buildPluginHash();
  const remoteHash = getRemotePluginHash();

  if (localHash === remoteHash && localHash !== '') {
    console.log('[dev:start] Plugin files unchanged. Skipping sync.');
    return true;
  }

  const prepStatus = run('docker', ['exec', WP_CONTAINER, 'sh', '-lc', `rm -rf ${pluginDir} && mkdir -p ${pluginDir}`]);
  if (prepStatus !== 0) {
    return false;
  }

  const archive = spawnSync('tar', buildTarArgs(), {
    encoding: null,
    stdio: ['ignore', 'pipe', 'inherit'],
  });
  if ((archive.status ?? 1) !== 0 || !archive.stdout) {
    return false;
  }

  const extract = spawnSync('docker', ['exec', '-i', WP_CONTAINER, 'tar', '-xf', '-', '-C', pluginDir], {
    input: archive.stdout,
    stdio: ['pipe', 'inherit', 'inherit'],
  });
  if ((extract.status ?? 1) !== 0) {
    return false;
  }

  if (run('docker', ['exec', WP_CONTAINER, 'sh', '-lc', `mkdir -p ${syncHashDir}`]) !== 0) {
    return false;
  }

  return run('docker', ['exec', WP_CONTAINER, 'sh', '-lc', `printf '%s' '${localHash}' > ${SYNC_HASH_FILE}`]) === 0;
}

function waitForCore() {
  for (let i = 1; i <= 30; i += 1) {
    if (runWpCli(['core', 'version', '--allow-root'], true).status === 0) {
      return true;
    }
    process.stdout.write(`[dev:start] Waiting for WordPress core... (${i}/30)\n`);
    Atomics.wait(new Int32Array(new SharedArrayBuffer(4)), 0, 0, 1000);
  }
  return false;
}

if (run('docker', ['--version'], { capture: true }).status !== 0) {
  console.error('[dev:start] Docker CLI was not found. Install Docker Desktop and retry.');
  process.exit(1);
}

if (!ensureNetwork(NETWORK) || !ensureVolume(DB_VOLUME) || !ensureVolume(WP_VOLUME)) {
  process.exit(1);
}

console.log('[dev:start] Starting DB container...');
if (!startDb()) {
  process.exit(1);
}

console.log('[dev:start] Starting WordPress container...');
if (!startWordPress()) {
  process.exit(1);
}

if (!waitForCore()) {
  console.error('[dev:start] WordPress did not become ready in time.');
  process.exit(1);
}

console.log('[dev:start] Syncing plugin files...');
if (!syncPluginFiles()) {
  process.exit(1);
}

run('docker', ['exec', WP_CONTAINER, 'chown', '-R', 'www-data:www-data', '/var/www/html/wp-content']);

const installed = runWpCli(['core', 'is-installed', '--allow-root'], true).status === 0;
if (!installed) {
  console.log('[dev:start] Installing WordPress...');
  const installStatus = runWpCli([
    'core',
    'install',
    `--url=${SITE_URL}`,
    '--title=WP Mailto Link Form Dev',
    '--admin_user=admin',
    '--admin_password=password',
    '--admin_email=admin@example.com',
    '--skip-email',
    '--allow-root',
  ]);
  if (installStatus !== 0) {
    process.exit(1);
  }
}

console.log('[dev:start] Activating plugin...');
if (runWpCli(['plugin', 'activate', PLUGIN_SLUG, '--allow-root']) !== 0) {
  process.exit(1);
}

console.log(`[dev:start] Site: ${SITE_URL}`);
console.log(`[dev:start] Admin: ${ADMIN_URL}`);
console.log('[dev:start] Login: admin / password');
