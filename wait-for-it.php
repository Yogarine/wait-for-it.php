#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * This section is only executed if this file is called as a standalone script.
 */
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $host    = null;
    $port    = null;
    $strict  = false;
    $quiet   = false;
    $verbose = false;
    $timeout = 15;

    $arguments = [];
    for ($i = 1; $i < $argc; $i++) {
        $argument = $argv[$i];

        if ('-' !== $argument[0]) {
            if (1 === $i) {
                $parts = explode(':', $argument, 2);
                $host  = $parts[0] ?? null;
                $port  = $parts[1] ?? null;
            } else {
                while ($i < $argc) {
                    $arguments[] = $argv[$i++];
                }

                break;
            }

            continue;
        }

        $option_parts = explode('=', $argument, 2);
        $option       = $option_parts[0];

        switch ($option) {
            case '-h':
            case '--host':
                $host = $option_parts[1] ?? $argv[++$i];
                break;
            case '-p':
            case '--port':
                $port = $option_parts[1] ?? $argv[++$i];
                break;
            case '-s':
            case '--strict':
                $strict = true;
                break;
            case '-q':
            case '--quiet':
                $quiet = true;
                break;
            case '-t':
            case '--timeout':
                $timeout = (int) ($option_parts[1] ?? $argv[++$i]);
                break;
            case '-v':
            case '--verbose':
                $verbose = true;
                break;
            case '--':
                $i++;
                while ($i < $argc) {
                    $arguments[] = $argv[$i++];
                }
                break 2;
            default:
                wait_for_it_echoerr("Unknown argument: {$option}");
                wait_for_it_show_usage_and_exit(1);
                break;
        }
    }

    if (! $host || ! $port) {
        if ($port) {
            wait_for_it_echoerr('Error: you need to provide a host');
        } elseif ($host) {
            wait_for_it_echoerr('Error: you need to provide a port');
        } else {
            wait_for_it_echoerr('Error: you need to provide a host and port');
        }

        wait_for_it_show_usage_and_exit(1);
    }

    if ($verbose) {
        echo "{$argv[0]}: Using these options:" . PHP_EOL
            . "\thost:    '$host'" . PHP_EOL
            . "\tport:    '$port'" . PHP_EOL
            . "\tstrict:  " . ($strict ? 'true' : 'false') . PHP_EOL
            . "\tquiet:   " . ($quiet ? 'true' : 'false') . PHP_EOL
            . "\tverbose: true" . PHP_EOL;
    }

    if ($timeout > 0) {
        wait_for_it_echoerr("{$argv[0]}: waiting {$timeout} sec for {$host}:{$port}");
    } else {
        wait_for_it_echoerr("{$argv[0]}: waiting for {$host}:{$port} without a timeout");
    }

    $result = wait_for_it("tcp://{$host}:{$port}", $timeout, $time_waited);
    if ($result) {
        wait_for_it_echoerr("{$argv[0]}: {$host}:{$port} is available after {$time_waited} seconds");
    } else {
        wait_for_it_echoerr("{$argv[0]}: timeout occurred after waiting {$time_waited} seconds for {$host}:{$port}");
    }

    if ($arguments) {
        if ($result || ! $strict) {
            $path = array_shift($arguments);
            wait_for_it_exec($path, $arguments);
        } else {
            wait_for_it_echoerr("{$argv[0]}: strict mode, refusing to execute subprocess");
        }
    }

    exit($result ? 0 : 1);
}

/**
 * @param  string    $path
 * @param  string[]  $arguments
 * @return void
 */
function wait_for_it_exec(string $path, array $arguments)
{
    $realpath = realpath($path);

    if (! $realpath) {
        $realpath = realpath(shell_exec('which ' . escapeshellarg($path)));
    }

    if (! $realpath) {
        $realpath = realpath(shell_exec('which sh'));
        if (! $realpath) {
            $realpath = '/bin/sh';
        }

        $arguments = ['-c', escapeshellcmd($path) . ' ' . implode(' ', array_map('escapeshellarg', $arguments))];
    }

    pcntl_exec($realpath, $arguments, getenv());
}

/**
 * @param  string  $message
 * @return void
 */
function wait_for_it_echoerr(string $message)
{
    global $quiet;
    static $stderr = null;

    if (! $quiet) {
        if (null === $stderr) {
            $stderr = fopen('php://stderr', 'wb');
        }

        fwrite($stderr, $message . PHP_EOL);
    }
}

/**
 * @param  int|string  $status  If status is a string, this function prints the status just before exiting.
 *                              If status is an integer, that value will be used as the exit status and not printed.
 *                              Exit statuses should be in the range 0 to 254, the exit status 255 is reserved by PHP
 *                              and shall not be used. The status 0 is used to terminate the program successfully.
 * @return void
 */
function wait_for_it_show_usage_and_exit($status = '')
{
    echo <<<USAGE
   wait-for-it.php <host>[:<port> | -p <port>] [options] [--] [command [args]]
   wait-for-it.php -h <host> -p <port> [options] [--] [command [args]]
   
   
  -h <host>, --host=<host>  Host or IP under test
  -p <port>, --port=<port>  TCP port under test. Alternatively, you specify the
                            host and port as host:port
  -s, --strict              Only execute subcommand if the test succeeds
  -q, --quiet               Don't output any status messages
  -t <timeout>, --timeout=<timeout>
                            Timeout in seconds, zero for no timeout
  -- command args             Execute command with args after the test finishes
USAGE;

    return exit($status);
}

/**
 * Waits for the given $address to become available, or until $timeout seconds go by, whichever comes first.
 *
 * @param  string  $address          Host address to try and connect to. In the standard URL format
 *                                   `transport://target`. So in reality this can be any PHP stream.
 * @param  int         $timeout      Number of seconds to wait for the host to become available.
 * @param  float|null  $time_waited  If set, will be filled with time waited for the host to become available.
 * @return bool True if successfully connected, false otherwise.
 */
function wait_for_it(string $address, int $timeout = 15, float &$time_waited = null): bool
{
    $start = microtime(true);

    $client = null;
    do {
        try {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            $client = @stream_socket_client($address, $errno, $errstr, 1, STREAM_CLIENT_CONNECT);
        } catch (Throwable $t) {
            // Ignore.
        }

        $time_waited = round(microtime(true) - $start, 3);
    } while (! $client && (0 === $timeout || $time_waited < $timeout));

    if ($client) {
        fclose($client);
        return true;
    }

    return false;
}
