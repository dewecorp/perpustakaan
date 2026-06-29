<?php
/**
 * Pemeriksaan update sistem dari GitHub (repo: dewecorp/perpustakaan).
 */

const GITHUB_REPO = 'dewecorp/perpustakaan';
const GITHUB_BRANCH = 'main';
const GITHUB_CHECK_CACHE_SECONDS = 3600;

function github_token(): string
{
    static $token = null;
    if ($token !== null) {
        return $token;
    }

    $token = '';
    $tokenFile = __DIR__ . '/github.token.php';
    if (is_file($tokenFile)) {
        $cfg = include $tokenFile;
        if (is_array($cfg)) {
            $token = trim((string)($cfg['token'] ?? ''));
        }
    }

    return $token;
}

function github_http_get(string $url, array $headers = []): ?string
{
    $defaultHeaders = [
        'User-Agent: PUSDIGI-UpdateChecker/1.0',
    ];
    $headers = array_merge($defaultHeaders, $headers);

    $token = github_token();
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'PUSDIGI-UpdateChecker/1.0',
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $body = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body !== false && $httpCode === 200) {
            return $body;
        }
    }

    $headerLines = implode("\r\n", $headers);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => $headerLines . "\r\n",
            'timeout' => 20,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    return is_string($body) && $body !== '' ? $body : null;
}

function github_api_get(string $url): ?array
{
    $body = github_http_get($url, ['Accept: application/vnd.github+json']);
    if ($body === null) {
        return null;
    }

    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}

function github_normalize_commit(array $commit): array
{
    return [
        'sha' => substr($commit['sha_full'], 0, 7),
        'sha_full' => $commit['sha_full'],
        'message' => $commit['message'],
        'date' => $commit['date'],
        'url' => $commit['url'],
    ];
}

function github_fetch_latest_commit_atom(): ?array
{
    $url = 'https://github.com/' . GITHUB_REPO . '/commits/' . GITHUB_BRANCH . '.atom';
    $body = github_http_get($url, ['Accept: application/atom+xml']);
    if ($body === null) {
        return null;
    }

    if (!preg_match(
        '/<entry>\s*<id>tag:github\.com,2008:Grit::Commit\/([a-f0-9]{40})<\/id>\s*<link[^>]+href="([^"]+)"/s',
        $body,
        $matches
    )) {
        return null;
    }

    $message = '';
    if (preg_match('/<entry>.*?<title>(.*?)<\/title>/s', $body, $titleMatch)) {
        $message = trim(html_entity_decode(strip_tags($titleMatch[1]), ENT_QUOTES, 'UTF-8'));
        $message = trim(strtok($message, "\n"));
    }

    $date = '';
    if (preg_match('/<entry>.*?<updated>([^<]+)<\/updated>/s', $body, $dateMatch)) {
        $date = trim($dateMatch[1]);
    }

    $shaFull = $matches[1];

    return github_normalize_commit([
        'sha_full' => $shaFull,
        'message' => $message,
        'date' => $date,
        'url' => $matches[2],
    ]);
}

function github_fetch_latest_commit_api(): ?array
{
    $url = 'https://api.github.com/repos/' . GITHUB_REPO . '/commits/' . GITHUB_BRANCH;
    $data = github_api_get($url);
    if (!$data || empty($data['sha'])) {
        return null;
    }

    $message = (string)($data['commit']['message'] ?? '');
    $message = trim(strtok($message, "\n"));

    return github_normalize_commit([
        'sha_full' => $data['sha'],
        'message' => $message,
        'date' => (string)($data['commit']['author']['date'] ?? ''),
        'url' => (string)($data['html_url'] ?? ('https://github.com/' . GITHUB_REPO . '/commit/' . $data['sha'])),
    ]);
}

function github_fetch_latest_commit(): ?array
{
    $commit = github_fetch_latest_commit_api();
    if ($commit) {
        return $commit;
    }

    return github_fetch_latest_commit_atom();
}

function github_bundled_commit(): array
{
    $file = __DIR__ . '/app_commit.php';
    if (!is_file($file)) {
        return ['sha_full' => '', 'date' => ''];
    }

    $cfg = include $file;
    if (!is_array($cfg)) {
        return ['sha_full' => '', 'date' => ''];
    }

    return [
        'sha_full' => trim((string)($cfg['sha'] ?? '')),
        'date' => trim((string)($cfg['date'] ?? '')),
    ];
}

function github_save_bundled_commit(string $sha, string $date): void
{
    $file = __DIR__ . '/app_commit.php';
    $content = "<?php\nreturn [\n    'sha' => " . var_export($sha, true) . ",\n    'date' => " . var_export($date, true) . ",\n];\n";
    file_put_contents($file, $content);
}

function github_installed_commit(): array
{
    $installedFull = get_setting('installed_commit_sha', '');
    $installedDate = get_setting('installed_commit_date', '');

    if ($installedFull !== '') {
        return [
            'sha_full' => $installedFull,
            'date' => $installedDate,
        ];
    }

    return github_bundled_commit();
}

function github_build_latest_from_cache(string $shaFull): array
{
    return [
        'sha' => substr($shaFull, 0, 7),
        'sha_full' => $shaFull,
        'message' => get_setting('github_latest_commit_message', ''),
        'date' => get_setting('github_latest_commit_date', ''),
        'url' => 'https://github.com/' . GITHUB_REPO . '/commit/' . $shaFull,
    ];
}

function github_update_result(array $installed, ?array $latest, ?int $checkedAt, ?string $error = null): array
{
    $installedFull = $installed['sha_full'];
    $installedShort = $installedFull !== '' ? substr($installedFull, 0, 7) : '';
    $hasUpdate = $latest !== null
        && $installedFull !== ''
        && $latest['sha_full'] !== $installedFull;

    if ($latest !== null && $installedFull === '') {
        $hasUpdate = true;
    }

    save_setting('github_update_has_update', $hasUpdate ? '1' : '0');

    return [
        'has_update' => $hasUpdate,
        'installed_sha' => $installedShort,
        'latest' => $latest,
        'checked_at' => $checkedAt,
        'error' => $error,
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
    github_save_bundled_commit($latestSha, $latestDate ?: date('c'));
}

/**
 * @return array{has_update:bool,installed_sha:string,latest:?array,checked_at:?int,error:?string}
 */
function check_github_update(bool $force = false): array
{
    $installed = github_installed_commit();
    $installedFull = $installed['sha_full'];
    $installedShort = $installedFull !== '' ? substr($installedFull, 0, 7) : '';
    $checkedAt = (int)get_setting('github_update_checked_at', '0');
    $cachedLatestFull = get_setting('github_latest_commit_sha', '');

    if (
        !$force
        && $checkedAt > 0
        && (time() - $checkedAt) < GITHUB_CHECK_CACHE_SECONDS
        && $cachedLatestFull !== ''
    ) {
        return github_update_result(
            $installed,
            github_build_latest_from_cache($cachedLatestFull),
            $checkedAt
        );
    }

    $latest = github_fetch_latest_commit();
    if ($latest) {
        save_setting('github_latest_commit_sha', $latest['sha_full']);
        save_setting('github_latest_commit_message', $latest['message']);
        save_setting('github_latest_commit_date', $latest['date']);
        save_setting('github_update_checked_at', (string)time());

        if ($installedFull === '') {
            $bundled = github_bundled_commit();
            if ($bundled['sha_full'] !== '') {
                save_setting('installed_commit_sha', $bundled['sha_full']);
                save_setting('installed_commit_date', $bundled['date']);
                $installed = $bundled;
            }
        }

        return github_update_result($installed, $latest, time());
    }

    if ($cachedLatestFull !== '') {
        return github_update_result(
            $installed,
            github_build_latest_from_cache($cachedLatestFull),
            $checkedAt ?: null,
            'Menggunakan data cache. GitHub API sementara tidak dapat diakses.'
        );
    }

    return [
        'has_update' => false,
        'installed_sha' => $installedShort,
        'latest' => null,
        'checked_at' => $checkedAt ?: null,
        'error' => 'Tidak dapat memeriksa pembaruan dari GitHub.',
    ];
}
