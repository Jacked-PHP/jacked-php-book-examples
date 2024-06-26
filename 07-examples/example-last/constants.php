<?php

// --------------------------
// Server status
// --------------------------

/**
 * Status for the server when it is dead.
 */
const SERVER_DEAD = 'dead';

/**
 * Status for the server when it is alive.
 */
const SERVER_ALIVE = 'alive';

// --------------------------
// Table keys
// --------------------------

/**
 * Manager Config for settings.
 * For available settings, check the "Config" section.
 */
const MANAGER_CONFIG_TABLE_KEY = 'manager_config';

/**
 * Status key for the HTTP server.
 * Used to keep the actual status.
 */
const HTTP_STATUS_TABLE_KEY = 'http_status';

/**
 * Temporary status key for the HTTP server.
 * Used to keep the previous status.
 */
const HTTP_TEMP_STATUS_TABLE_KEY = 'http_temp_status';

/**
 * Temporary status key for the HTTP server monitor.
 * Used to keep the previous status for the Monitor process.
 */
const HTTP_MONITOR_TEMP_STATUS_TABLE_KEY = 'http_monitor_temp_status';

/**
 * Key for the WebSocket actions.
 * Used to keep the WebSocket action in execution.
 */
const WS_TABLE_KEY = 'ws_input';

/**
 * Key for the WebSocket timers.
 */
const WS_TIMERS_TABLE_KEY = 'ws_timers';

// --------------------------
// Config
// --------------------------

/**
 * Config key to keep the server alive
 */
const KEEP_ALIVE = 'keep_alive';

// --------------------------
// Process names
// --------------------------

/**
 * Name for the HTTP server process.
 */
const HTTP_PROCESS_NAME = 'openswoole-http-server';

/**
 * Name for the WebSocket server process.
 */
const WS_PROCESS_NAME = 'openswoole-websocket-server';

// --------------------------
// Settings
// --------------------------

/**
 * Timer Interval.
 */
const TIMER_INTERVAL = 500;
