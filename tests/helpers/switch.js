// Makes a switch of the live site go wrong on purpose (tests/fixtures/
// switch-faults.php). The test site is a folder on this machine, here and in CI.
import { copyFileSync, existsSync, mkdirSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';

const CONTENT = process.env.BRODFEST_WP_CONTENT || fileURLToPath(new URL('../../.wp-test/wp/wp-content', import.meta.url));
const PLUGIN = join(CONTENT, 'mu-plugins', 'brodfest-switch-faults.php');
const FAULT = join(CONTENT, 'switch-fault.json');
const REPORT = join(CONTENT, 'switch-report.json');

export function installFaults() {
  if (!existsSync(CONTENT)) throw new Error(`No test site at ${CONTENT}. Start it with wp-test-env.mjs --dir .wp-test.`);
  mkdirSync(join(CONTENT, 'mu-plugins'), { recursive: true });
  copyFileSync(fileURLToPath(new URL('../fixtures/switch-faults.php', import.meta.url)), PLUGIN);
}

export function removeFaults() {
  for (const file of [PLUGIN, FAULT, REPORT]) rmSync(file, { force: true });
}

// The fault for every request from now on; none to stop.
export function fault(what) {
  rmSync(REPORT, { force: true });
  if (what) writeFileSync(FAULT, JSON.stringify(what));
  else rmSync(FAULT, { force: true });
}

// What the fixture saw.
export function report() {
  return existsSync(REPORT) ? JSON.parse(readFileSync(REPORT, 'utf8')) : {};
}
