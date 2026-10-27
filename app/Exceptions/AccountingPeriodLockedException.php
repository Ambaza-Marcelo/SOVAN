<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Levée lorsqu'une écriture vise une période verrouillée ou un exercice clôturé.
 */
class AccountingPeriodLockedException extends RuntimeException
{
}
