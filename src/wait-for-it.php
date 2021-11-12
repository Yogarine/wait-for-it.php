<?php

declare(strict_types=1);

/**
 * @param  string    $path
 * @param  string[]  $arguments
 * @return void
 */
function wait_for_it_exec(string $path, array $arguments)
{
    $realpath = realpath($path);

    if (! $realpath) {
        $whichPath = shell_exec('which ' . escapeshellarg($path));
        if ($whichPath) {
            $realpath = realpath($whichPath);
        }
    }

    if (! $realpath) {
        $whichPath = shell_exec('which sh');
        if ($whichPath) {
            $realpath = realpath($whichPath);
        }

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
 * @param  string      $address      Host address to try and connect to. In the standard URL format
 *                                   `transport://target`. So in reality this can be any PHP stream.
 * @param  int         $timeout      Number of seconds to wait for the host to become available.
 * @param  float|null  $time_waited  If set, will be filled with time waited for the host to become available.
 * @return bool True if successfully connected, false otherwise.
 */
function wait_for_it(string $address, int $timeout = 15, float &$time_waited = null): bool
{
    $start = microtime(true);

    $interval_time_sec = 1;
    $client = null;
    do {
        $wait_start = microtime(true);

        try {
            /** @noinspection PhpUsageOfSilenceOperatorInspection */
            $client = @stream_socket_client($address, $errno, $errstr, $interval_time_sec, STREAM_CLIENT_CONNECT);
        } catch (Throwable $t) {
            // Ignore.
        }

        $time_waited = round(microtime(true) - $start, 3);

        if ($client) {
            fclose($client);
            return true;
        }

        $interval = microtime(true) - $wait_start;
        if ($interval < $interval_time_sec) {
            usleep((int) round(1000000 * ($interval_time_sec - $interval)));
        }
    } while (0 === $timeout || $time_waited < $timeout);

    return false;
}
