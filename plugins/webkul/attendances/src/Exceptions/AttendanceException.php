<?php

namespace Webkul\Attendance\Exceptions;

use RuntimeException;

/**
 * A refusal the signed-in user can act on; the message is already translated and safe to display.
 */
class AttendanceException extends RuntimeException {}
