<?php

namespace App\Actions\Outreach;

use RuntimeException;

/**
 * A required PDF could not be rendered during Approve; the message is safe
 * to show, the previous exception holds the technical cause.
 */
class PdfGateFailure extends RuntimeException {}
