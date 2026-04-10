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

function hasCommand(command, args) {
  const check = spawnSync(command, args, { stdio: 'ignore' });

  return (check.status ?? 1) === 0;
}

function getArchiveTool() {
  if (hasCommand('zip', ['-v'])) {
    return 'zip';
  }

  if (process.platform === 'win32') {
    if (hasCommand('powershell.exe', ['-NoProfile', '-Command', '$PSVersionTable.PSVersion.ToString()'])) {
      return 'powershell';
    }

    if (hasCommand('pwsh.exe', ['-NoProfile', '-Command', '$PSVersionTable.PSVersion.ToString()'])) {
      return 'pwsh';
    }
  }

  fail('Neither `zip` nor PowerShell archive support is available. Install zip and retry.');
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
  rmSync(join(stageDir, 'assets', 'screenshot-1.png'), { force: true });
  rmSync(join(stageDir, 'assets', 'screenshot-2.png'), { force: true });
  rmSync(join(stageDir, 'assets', 'screenshot-3.png'), { force: true });
  rmSync(join(stageDir, 'includes', '.gitkeep'), { force: true });
  rmSync(join(stageDir, 'languages', '.gitkeep'), { force: true });
}

function buildZip(version, archiveTool) {
  mkdirSync(distDir, { recursive: true });
  const fileName = `${pluginSlug}-${version}.zip`;
  const zipPath = resolve(distDir, fileName);

  rmSync(zipPath, { force: true });

  let result;

  if (archiveTool === 'zip') {
    result = spawnSync('zip', ['-r', zipPath, pluginSlug], {
      cwd: tempRoot,
      stdio: 'inherit',
    });
  } else {
    const shell = archiveTool === 'powershell' ? 'powershell.exe' : 'pwsh.exe';
    const escapedStageDir = stageDir.replace(/'/g, "''");
    const escapedZipPath = zipPath.replace(/'/g, "''");
    const command = [
      `$source = Resolve-Path -LiteralPath '${escapedStageDir}'`,
      `Compress-Archive -LiteralPath $source -DestinationPath '${escapedZipPath}' -Force`,
    ].join('; ');

    result = spawnSync(shell, ['-NoProfile', '-NonInteractive', '-Command', command], {
      stdio: 'inherit',
    });
  }

  if ((result.status ?? 1) !== 0) {
    fail('archive command failed.');
  }

  return zipPath;
}

const version = getPluginVersion();
const archiveTool = getArchiveTool();
copyReleaseFiles();
const zipPath = buildZip(version, archiveTool);
rmSync(tempRoot, { recursive: true, force: true });

console.log(`[release:zip] Created: ${zipPath}`);
