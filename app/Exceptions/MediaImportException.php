<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown for whole-ZIP media problems (over a size/entry limit, zip-slip path
 * traversal, duplicate basenames). The import still applies the data rows — the
 * ZIP is simply rejected and recorded as a warning.
 */
final class MediaImportException extends RuntimeException {}
