#!/usr/bin/env node
import { cpSync, existsSync, mkdirSync, readFileSync, rmSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';

const projectRoot = process.cwd();
const pluginSlug = 'mailto-link-form';
const mainPluginFileName = 'mailto-link-form.php';
const mainPluginFile = join(projectRoot, mainPluginFileName);
const distDir = join(projectRoot, 'dist');
const tempRoot = join(projectRoot, '.release-tmp');
const stageDir = join(tempRoot, pluginSlug);

const includePaths = [
  mainPluginFileName,
  'uninstall.php',
  'readme.txt',
  'assets',
  'includes',
  'languages',
];

function fail(message) {
  console.error(`[release:zip] ${message}`);
  process.exit(1);
}

function getPluginVersion() {
  if (!existsSync(mainPluginFile)) {
    fail(`Main plugin file not found: ${mainPluginFile}`);
  }

  const source = readFileSync(mainPluginFile, 'utf8');
  const versionMatch = source.match(/^\s*\*\s*Version:\s*(.+)$/m);

  if (!versionMatch) {
    fail(`Version header not found in ${mainPluginFileName}`);
  }

  return versionMatch[1].trim();
}

function assertZipCommand() {
  const check = spawnSync('zip', ['-v'], { stdio: 'ignore' });
  if ((check.status ?? 1) !== 0) {
    fail('`zip` command is not available. Install zip and retry.');
  }
}

function copyReleaseFiles() {
  rmSync(tempRoot, { recursive: true, force: true });
  mkdirSync(stageDir, { recursive: true });

  for (const item of includePaths) {
    const src = join(projectRoot, item);
    const dest = join(stageDir, item);

    if (!existsSync(src)) {
      fail(`Required release file is missing: ${item}`);
    }

    cpSync(src, dest, { recursive: true });
  }

  rmSync(join(stageDir, 'assets', '.gitkeep'), { force: true });
  rmSync(join(stageDir, 'includes', '.gitkeep'), { force: true });
  rmSync(join(stageDir, 'languages', '.gitkeep'), { force: true });
}

function buildZip(version) {
  mkdirSync(distDir, { recursive: true });
  const fileName = `${pluginSlug}-${version}.zip`;
  const zipPath = resolve(distDir, fileName);

  rmSync(zipPath, { force: true });

  const result = spawnSync('zip', ['-r', zipPath, pluginSlug], {
    cwd: tempRoot,
    stdio: 'inherit',
  });

  if ((result.status ?? 1) !== 0) {
    fail('zip command failed.');
  }

  return zipPath;
}

assertZipCommand();
const version = getPluginVersion();
copyReleaseFiles();
const zipPath = buildZip(version);
rmSync(tempRoot, { recursive: true, force: true });

console.log(`[release:zip] Created: ${zipPath}`);
