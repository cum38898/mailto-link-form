#!/usr/bin/env node
import { spawnSync } from 'node:child_process';

const CONTAINERS = ['malifo-wordpress', 'malifo-db'];

function run(command, args) {
  const result = spawnSync(command, args, { stdio: 'inherit' });
  return result.status ?? 1;
}

function existsContainer(name) {
  return spawnSync('docker', ['container', 'inspect', name], { stdio: 'ignore' }).status === 0;
}

for (const container of CONTAINERS) {
  if (existsContainer(container)) {
    run('docker', ['stop', container]);
  }
}

console.log('[dev:stop] Stopped malifo-wordpress and malifo-db (if they existed).');
