#!/usr/bin/env node
import { spawnSync } from 'node:child_process';

const CONTAINERS = ['malifo-wordpress', 'malifo-db'];
const VOLUMES = ['wpmlf-wp-data', 'wpmlf-db-data'];
const NETWORK = 'wpmlf-net';

function run(command, args) {
  const result = spawnSync(command, args, { stdio: 'inherit' });
  return result.status ?? 1;
}

function exists(type, name) {
  return spawnSync('docker', [type, 'inspect', name], { stdio: 'ignore' }).status === 0;
}

let hasError = false;

for (const container of CONTAINERS) {
  if (exists('container', container)) {
    if (run('docker', ['rm', '-f', container]) !== 0) {
      hasError = true;
    }
  }
}

for (const volume of VOLUMES) {
  if (exists('volume', volume)) {
    if (run('docker', ['volume', 'rm', volume]) !== 0) {
      hasError = true;
    }
  }
}

if (exists('network', NETWORK)) {
  if (run('docker', ['network', 'rm', NETWORK]) !== 0) {
    hasError = true;
  }
}

if (hasError) {
  console.error('[dev:reset] Reset completed with errors. See output above.');
  process.exit(1);
}

console.log('[dev:reset] Removed containers, volumes, and network.');
