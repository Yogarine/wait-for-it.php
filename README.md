# wait-for-it.php
wait-for-it.php is a PHP implementation of the
[wait-for-it.sh script by Gilles Hall](https://github.com/vishnubob/wait-for-it).

It can be both run as a standalone executable or included in PHP and used by
calling the wait_for_it() function. As standalone script it can be used as a
drop-in replacement for wait-for-it.sh.

## Usage

### From the command line

```text
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
```

### From PHP:

```php
function wait_for_it(string $address, int $timeout = 15, float &$time_waited = null): bool {}
```

## Installation 
### As Composer package

You can install it globally to use it as a stand-alone command:
```bash
composer global require yogarine/wait-for-it
```

You can also install wait-for-it.php as a dependency of your project:
```bash
composer require yogarine/wait-for-it
```

This will allow you to use the `wait`


### Docker

wait-for-it.php is also available as a Docker image:
```bash
docker run yogarine/wait-for-it www.google.com:80 -- echo "google is up"
```

You can also easily copy the script in your own Dockerfiles:
```dockerfile
# Copy wait-for-it.php from it's official Docker image.
COPY --from=yogarine/wait-for-it /usr/local/bin/wait-for-it.php /usr/local/bin/
```
Just keep in mind you'll need to have the pcntl extension installed.


## Usage in PHP code

When installed as composer package `wait-for-it.php` is automatically included
as helper file, and declares the `wait_for_it()` function.

It will wait and return `true` 


## Examples

For example, let's test to see if we can access port 80 on `www.google.com`,
and if it is available, echo the message `google is up`.

```bash
$ ./wait-for-it.php www.google.com:80 -- echo "google is up"
wait-for-it.php: waiting 15 seconds for www.google.com:80
wait-for-it.php: www.google.com:80 is available after 0 seconds
google is up
```

You can set your own timeout with the `-t` or `--timeout=` option.  Setting
the timeout value to 0 will disable the timeout:

```bash
$ ./wait-for-it.php -t 0 www.google.com:80 -- echo "google is up"
wait-for-it.php: waiting for www.google.com:80 without a timeout
wait-for-it.php: www.google.com:80 is available after 0 seconds
google is up
```

The subcommand will be executed regardless if the service is up or not.  If you
wish to execute the subcommand only if the service is up, add the `--strict`
argument. In this example, we will test port 81 on `www.google.com` which will
fail:

```bash
$ ./wait-for-it.php www.google.com:81 --timeout=1 --strict -- echo "google is up"
wait-for-it.php: waiting 1 seconds for www.google.com:81
wait-for-it.php: timeout occurred after waiting 1 seconds for www.google.com:81
wait-for-it.php: strict mode, refusing to execute subprocess
```

If you don't want to execute a subcommand, leave off the `--` argument.  This
way, you can test the exit condition of `wait-for-it.sh` in your own scripts,
and determine how to proceed:

```bash
$ ./wait-for-it.sh www.google.com:80
wait-for-it.php: waiting 15 seconds for www.google.com:80
wait-for-it.php: www.google.com:80 is available after 0 seconds
$ echo $?
0
$ ./wait-for-it.sh www.google.com:81
wait-for-it.php: waiting 15 seconds for www.google.com:81
wait-for-it.php: timeout occurred after waiting 15 seconds for www.google.com:81
$ echo $?
124
```

## Community

*Debian*: There is a [Debian package](https://tracker.debian.org/pkg/wait-for-it).
