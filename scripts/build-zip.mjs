#!/usr/bin/env node
// Builds the deployable plugin zip from an explicit allowlist, then verifies
// the archive it just wrote. Never Compress-Archive: on Windows it writes
// backslash entries and WordPress then reports "Plugin file does not exist."

import { execFileSync } from 'node:child_process';
import { cpSync, existsSync, mkdirSync, readdirSync, readFileSync, rmSync } from 'node:fs';
import { join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const SLUG = 'blueworx-client-brodfest';
const MAIN_FILE = `${SLUG}.php`;

// Everything that ships, and nothing else. A new development directory is
// excluded because nobody added it here, rather than shipped because nobody
// remembered to exclude it.
const ALLOW = [
	MAIN_FILE,
	'uninstall.php',
	'readme.txt',
	'includes',
	'templates',
	// Only the built runtime assets — not assets/js/src or assets/css/src,
	// which are build inputs. includes/assets.php only ever enqueues
	// assets/css/public.css and assets/js/public.js, so the source trees
	// are dead weight nothing on a live site loads.
	'assets/css/public.css',
	'assets/js/public.js',
	'assets/img',
	'assets/video',
	// The admin design system and page editor, shipped verbatim (CI compares
	// each against the foundation). assets/fonts is the design system's own
	// webfonts, loaded from beside its stylesheet.
	'assets/blueworx-admin-design.css',
	'assets/blueworx-admin-design.php',
	'assets/blueworx-admin-icons.js',
	'assets/blueworx-page-editor.js',
	'assets/fonts',
	'blueworx-page-editor',
	// The vendored update checker. Without it the plugin fatals on activation,
	// since the main file requires it unconditionally.
	'plugin-update-checker',
];

// Staged by the allowlist above, then removed. Kept as a deny list rather than
// by pruning the vendored library in the repo, so upgrading it stays a
// wholesale folder swap.
//
// Currently empty: this vendored copy of the update checker carries no
// composer.json (the earlier entry for one matched nothing). Entries belong
// here when a shipped directory contains a file that must not reach a site —
// a composer.json anywhere in the archive fails the release content check.
const DENY = [];

// bsdtar writes forward slashes on every platform and can read/write zip.
// On Windows the System32 copy is bsdtar; whatever `tar` resolves to first
// on PATH (e.g. Git Bash's GNU tar) cannot read zip archives.
export function resolveTar() {
	return process.platform === 'win32' ? `${process.env.WINDIR}\\System32\\tar.exe` : 'tar';
}

// The version ships in the artifact's filename and must match what a site
// has installed, so it is read from the plugin header itself — not
// package.json, which this plugin has for tooling only and is not shipped.
function readVersion() {
	const header = readFileSync(MAIN_FILE, 'utf8');
	const match = header.match(/^\s*\*\s*Version:\s*(.+)$/m);
	if (!match) {
		throw new Error(`could not find a Version: header in ${MAIN_FILE}`);
	}
	return match[1].trim();
}

function removeExistingZips(root) {
	for (const entry of readdirSync(root)) {
		if (entry.startsWith(`${SLUG}`) && entry.endsWith('.zip')) {
			rmSync(join(root, entry), { force: true });
		}
	}
}

function build() {
	const version = readVersion();
	const stage = join('dist', SLUG);
	const parent = resolve('..');
	const zip = join('..', `${SLUG}-${version}.zip`);

	rmSync('dist', { recursive: true, force: true });
	mkdirSync(stage, { recursive: true });

	for (const entry of ALLOW) {
		if (!existsSync(entry)) {
			if (entry === MAIN_FILE) {
				throw new Error(`${MAIN_FILE} is missing — nothing to ship`);
			}
			continue;
		}
		cpSync(entry, join(stage, entry), { recursive: true });
	}

	for (const entry of DENY) {
		rmSync(join(stage, entry), { recursive: true, force: true });
	}

	// Exactly one archive for this plugin is ever present alongside the repo.
	removeExistingZips(parent);

	const tar = resolveTar();
	execFileSync(tar, ['-a', '-c', '-f', zip, '-C', 'dist', SLUG], { stdio: 'inherit' });

	const entries = execFileSync(tar, ['-tf', zip], { encoding: 'utf8' })
		.split(/\r?\n/)
		.filter(Boolean);

	if (entries.length === 0) {
		throw new Error('the archive is empty');
	}

	for (const entry of entries) {
		if (entry.includes('\\')) {
			throw new Error(`backslash entry in the zip: ${entry}`);
		}
		if (!entry.startsWith(`${SLUG}/`)) {
			throw new Error(`entry outside ${SLUG}/: ${entry}`);
		}
	}
	if (!entries.includes(`${SLUG}/${MAIN_FILE}`)) {
		throw new Error(`${SLUG}/${MAIN_FILE} is not in the archive`);
	}

	console.log(`Built ${zip} — ${entries.length} entries.`);
	for (const entry of entries) {
		console.log(entry);
	}
}

// Only run when executed directly, not when imported.
const isMain = process.argv[1] && fileURLToPath(import.meta.url) === resolve(process.argv[1]);
if (isMain) {
	build();
}
