// Builds the plugin's committed front-end assets: one stylesheet from Tailwind,
// one JS bundle from esbuild. Both outputs are committed, so the plugin renders
// from a plain checkout and the zip needs no Node step.
import { execFileSync } from 'node:child_process';
import { build } from 'esbuild';
import { existsSync } from 'node:fs';

const dev = process.argv.includes('--watch');

execFileSync(
  process.platform === 'win32' ? 'npx.cmd' : 'npx',
  [
    '@tailwindcss/cli',
    '-i', 'assets/css/src/app.css',
    '-o', 'assets/css/public.css',
    ...(dev ? ['--watch'] : ['--minify']),
  ],
  { stdio: 'inherit', shell: process.platform === 'win32' }
);

if (existsSync('assets/js/src/main.js')) {
  await build({
    entryPoints: ['assets/js/src/main.js'],
    bundle: true,
    minify: !dev,
    format: 'iife',
    target: ['es2020'],
    outfile: 'assets/js/public.js',
    logLevel: 'info',
  });
}
