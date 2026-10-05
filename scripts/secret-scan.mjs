#!/usr/bin/env node
/**
 * Keeps real credentials out of the public repository – without any extra tool installed:
 *
 *   node scripts/secret-scan.mjs            # every tracked file (part of npm run verify)
 *   node scripts/secret-scan.mjs --staged   # only what is about to be committed (.githooks/pre-commit)
 *
 * Second line of defence next to gitleaks (CI, full history) and GitHub push protection. The most likely leak in this
 * project is a real Google Maps API key pasted into a blueprint, test or screenshot script instead of "TEST-KEY",
 * so that pattern comes first. Findings are printed redacted.
 */
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { root } from './lib/php.mjs';

const staged = process.argv.includes('--staged');

const RULES = [
	['Google API key', /AIza[0-9A-Za-z_-]{35}/g],
	['Google OAuth client secret', /GOCSPX-[0-9A-Za-z_-]{20,}/g],
	['Google service account key', /"type":\s*"service_account"/g],
	['GitHub token', /\b(?:gh[pousr]_[0-9A-Za-z]{36,}|github_pat_[0-9A-Za-z_]{40,})\b/g],
	['Private key', /-----BEGIN (?:RSA |EC |OPENSSH |DSA |PGP )?PRIVATE KEY( BLOCK)?-----/g],
	['OpenAI / Anthropic key', /\bsk-(?:ant-|proj-)?[0-9A-Za-z_-]{32,}/g],
	['Slack token', /\bxox[abprs]-[0-9A-Za-z-]{10,}/g],
	['AWS access key', /\b(?:AKIA|ASIA)[0-9A-Z]{16}\b/g],
];

// Files that must never be committed, whatever they contain.
const FORBIDDEN_FILES = /(^|\/)(\.env(\..+)?|auth\.json|.*\.(pem|key|p12|pfx)|.*service-account.*\.json|wp-config(-local)?\.php)$/i;

const git = (...args) => {
	const res = spawnSync('git', args, { cwd: root, encoding: 'utf8', maxBuffer: 256 * 1024 * 1024 });
	if (res.status !== 0) throw new Error(`git ${args.join(' ')} failed: ${res.stderr}`);
	return res.stdout;
};

const files = (staged
	? git('diff', '--cached', '--name-only', '--diff-filter=ACMR', '-z')
	: git('ls-files', '-z')
).split('\0').filter(Boolean);

const findings = [];
for (const file of files) {
	if (FORBIDDEN_FILES.test(file) && !file.endsWith('.example')) {
		findings.push(`${file}: file type must never be committed (add it to .gitignore)`);
		continue;
	}
	// The staged scan reads the index (what gets committed), the full scan the working tree.
	let content = null;
	if (staged) {
		const res = spawnSync('git', ['show', `:${file}`], { cwd: root, encoding: 'latin1', maxBuffer: 64 * 1024 * 1024 });
		content = res.status === 0 ? res.stdout : null;
	} else if (fs.existsSync(path.join(root, file))) {
		content = fs.readFileSync(path.join(root, file), 'latin1');
	}
	if (content === null || content.includes('\0')) continue; // deleted or binary
	const lines = content.split('\n');
	for (const [name, pattern] of RULES) {
		lines.forEach((line, i) => {
			for (const match of line.matchAll(pattern)) {
				findings.push(`${file}:${i + 1}: ${name} (${match[0].slice(0, 6)}…redacted)`);
			}
		});
	}
}

// Untracked files are not committed yet, but a full scan should still cover them (e.g. before the first commit).
if (!staged) {
	for (const file of git('ls-files', '--others', '--exclude-standard', '-z').split('\0').filter(Boolean)) {
		if (FORBIDDEN_FILES.test(file) && !file.endsWith('.example')) {
			findings.push(`${file}: untracked, not ignored – file type must never be committed (add it to .gitignore)`);
		}
	}
}

if (findings.length) {
	console.error('✗ Possible secrets found – nothing may be committed or pushed like this:\n');
	for (const f of findings) console.error(`  ${f}`);
	console.error('\nMove the value into WordPress (settings page) or a git-ignored local file. Test data uses the key "TEST-KEY".');
	console.error('Already committed? Rotate the key in the Google Cloud Console first – removing it from Git is not enough.');
	process.exit(1);
}
console.log(`✓ No secrets in ${files.length} ${staged ? 'staged' : 'tracked'} file(s).`);
