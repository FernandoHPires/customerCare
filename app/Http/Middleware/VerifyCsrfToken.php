<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        'web/check-session',
        'web/merge/merge-documents',
        'web/merge/view-documents',
        'web/initialization/check-active-quotes',
        'web/initialization/quote-selected',
        'web/pap/bank-info/institution-name',
        'web/initialization/initialize',
        'web/applicants/reorg',
        'web/applicants/reorg/*',
        'web/mailings',
        'web/mailings/*',
        'web/contact-center',
        'web/contact-center/*',
        'web/application-dashboard',
        'web/application-dashboard/*',
        'web/quote',
        'web/quote/*',
        'web/checklist',
        'web/checklist/*',
        'web/edit-notes',
        'web/edit-notes/*',
        'web/nearby-mortgages',
        'web/nearby-mortgages/*',
    ];
}
