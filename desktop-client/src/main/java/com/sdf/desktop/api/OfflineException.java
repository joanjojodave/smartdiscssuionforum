package com.sdf.desktop.api;

/** Thrown when the backend cannot be reached at all (no connectivity). */
public class OfflineException extends Exception {
    public OfflineException(Throwable cause) {
        super("Server is unreachable", cause);
    }
}
