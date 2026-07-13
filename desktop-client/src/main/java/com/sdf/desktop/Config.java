package com.sdf.desktop;

import java.io.File;

/**
 * Runtime configuration for the desktop client: where the Laravel backend
 * lives and where local offline data is cached on disk.
 */
public final class Config {

    /** Overridable via -Dsdf.apiBaseUrl=... for pointing at a deployed backend. */
    public static String apiBaseUrl = System.getProperty("sdf.apiBaseUrl", "https://smartdiscssuionforum-production.up.railway.app/api");

    public static final File APP_DIR = new File(System.getProperty("user.home"), ".smart-discussion-forum");

    public static final File LOCAL_DB_FILE = new File(APP_DIR, "local-cache.sqlite");

    /** How often the background sync timer ticks, in milliseconds. */
    public static final int SYNC_INTERVAL_MS = 15_000;

    private Config() {}
}
