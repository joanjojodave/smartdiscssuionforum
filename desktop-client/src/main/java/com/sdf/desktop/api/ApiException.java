package com.sdf.desktop.api;

/** Thrown for HTTP-level failures (4xx/5xx) once a response was actually received. */
public class ApiException extends Exception {
    public final int statusCode;

    public ApiException(int statusCode, String message) {
        super(message);
        this.statusCode = statusCode;
    }
}
