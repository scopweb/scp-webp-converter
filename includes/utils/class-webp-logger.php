<?php
/**
 * Sistema de logging para el plugin WebP
 * 
 * @package SCP_WebP_Converter
 * @version 1.3.0
 */

if (!defined('ABSPATH')) exit;

class SCP_WebP_Logger {

    /**
     * Sistema de logging mejorado
     * 
     * @param string $message Mensaje a registrar
     * @param string $level Nivel de log (info, warning, error, debug)
     * @param array $context Contexto adicional opcional
     */
    public static function log(string $message, string $level = 'info', array $context = []) {
        if (!WP_DEBUG_LOG) return;

        $timestamp = current_time('Y-m-d H:i:s');
        $formatted_message = sprintf(
            '[%s] SCP WebP Converter [%s]: %s',
            $timestamp,
            strtoupper($level),
            $message
        );

        if (!empty($context)) {
            $formatted_message .= ' Context: ' . wp_json_encode($context);
        }

        error_log($formatted_message);
    }

    /**
     * Log de información
     */
    public static function info(string $message, array $context = []) {
        self::log($message, 'info', $context);
    }

    /**
     * Log de advertencia
     */
    public static function warning(string $message, array $context = []) {
        self::log($message, 'warning', $context);
    }

    /**
     * Log de error
     */
    public static function error(string $message, array $context = []) {
        self::log($message, 'error', $context);
    }

    /**
     * Log de depuración
     */
    public static function debug(string $message, array $context = []) {
        self::log($message, 'debug', $context);
    }
}