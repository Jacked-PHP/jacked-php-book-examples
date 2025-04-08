
It was decided to run the HTTP server via shell commands due to a constraint related to how the `OpenSwoole\Process` class works. It has to be forked from outside a Coroutine environment.
