<?php
/**
 * Pemeriksaan update sistem dari GitHub (repo: dewecorp/perpustakaan).
 */

const GITHUB_REPO = 'dewecorp/perpustakaan';
const GITHUB_BRANCH = 'main';
const GITHUB_CHECK_CACHE_SECONDS = 3600;

function github_api_get(string $url): ?array
{
    if (!function_exists('curl_init')) {
        return null;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => 'PUSDIGI-UpdateChecker/1.0',
        CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json'],
    ]);
    $body = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $httpCode !== 200) {
        return null;
    }

    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}

function github_fetch_latest_commit(): ?array
{
    $url = 'https://api.github.com/repos/' . GITHUB_REPO . '/commits/' . GITHUB_BRANCH;
    $data = github_api_get($url);
    if (!$data || empty($data['sha'])) {
        return null;
    }

    $message = (string)($data['commit']['message'] ?? '');
    $message = trim(strtok($message, "\n"));

    return [
        'sha' => substr($data['sha'], 0, 7),
        'sha_full' => $data['sha'],
        'message' => $message,
        'date' => (string)($data['commit']['author']['date'] ?? ''),
        'url' => (string)($data['html_url'] ?? ('https://github.com/' . GITHUB_REPO . '/commits/' . $data['sha'])),
    ];
}

function mark_github_update_installed(): void
{
    $latestSha = get_setting('github_latest_commit_sha', '');
    $latestDate = get_setting('github_latest_commit_date', '');

    if ($latestSha === '') {
        $commit = github_fetch_latest_commit();
        if (!$commit) {
            return;
        }
        $latestSha = $commit['sha_full'];
        $latestDate = $commit['date'];
        save_setting('github_latest_commit_sha', $latestSha);
        save_setting('github_latest_commit_message', $commit['message']);
        save_setting('github_latest_commit_date', $latestDate);
    }

    save_setting('installed_commit_sha', $latestSha);
    save_setting('installed_commit_date', $latestDate ?: date('c'));
    save_setting('github_update_has_update', '0');
}

/**
 * @return array{has_update:bool,installed_sha:string,latest:?array,checked_at:?int,error:?string}
 */
function check_github_update(bool $force = false): array
{
    $installedFull = get_setting('installed_commit_sha', '');
    $installedShort = $installedFull !== '' ? substr($installedFull, 0, 7) : '';
    $checkedAt = (int)get_setting('github_update_checked_at', '0');
    $cachedLatestFull = get_setting('github_latest_commit_sha', '');

    if (
        !$force
        && $checkedAt > 0
        && (time() - $checkedAt) < GITHUB_CHECK_CACHE_SECONDS
        && $cachedLatestFull !== ''
    ) {
        $hasUpdate = $installedFull !== '' && $cachedLatestFull !== $installedFull;
        return [
            'has_update' => $hasUpdate,
            'installed_sha' => $installedShort,
            'latest' => [
                'sha' => substr($cachedLatestFull, 0, 7),
                'sha_full' => $cachedLatestFull,
                'message' => get_setting('github_latest_commit_message', ''),
                'date' => get_setting('github_latest_commit_date', ''),
                'url' => 'https://github.com/' . GITHUB_REPO . '/commits/' . $cachedLatestFull,
            ],
            'checked_at' => $checkedAt,
            'error' => null,
        ];
    }

    $latest = github_fetch_latest_commit();
    if (!$latest) {
        return [
            'has_update' => false,
            'installed_sha' => $installedShort,
            'latest' => null,
            'checked_at' => $checkedAt ?: null,
            'error' => 'Tidak dapat memeriksa pembaruan dari GitHub.',
        ];
    }

    save_setting('github_latest_commit_sha', $latest['sha_full']);
    save_setting('github_latest_commit_message', $latest['message']);
    save_setting('github_latest_commit_date', $latest['date']);
    save_setting('github_update_checked_at', (string)time());

    if ($installedFull === '') {
        save_setting('installed_commit_sha', $latest['sha_full']);
        save_setting('installed_commit_date', $latest['date']);
        $installedFull = $latest['sha_full'];
        $installedShort = $latest['sha'];
    }

    $hasUpdate = $latest['sha_full'] !== $installedFull;
    save_setting('github_update_has_update', $hasUpdate ? '1' : '0');

    return [
        'has_update' => $hasUpdate,
        'installed_sha' => $installedShort,
        'latest' => $latest,
        'checked_at' => time(),
        'error' => null,
    ];
}
