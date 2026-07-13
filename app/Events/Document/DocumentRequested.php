<?php
declare(strict_types=1);
namespace App\Events\Document;
// Re-export from App\Events\Plan for backwards compat with AppServiceProvider listener registration.
class DocumentRequested extends \App\Events\Plan\DocumentRequested {}
