<?php

namespace App\Services;

use HTMLPurifier_Config;
use Mews\Purifier\Facades\Purifier;

class NoteContentSanitizer
{
    /**
     * Sanitize note HTML, preserving mention span data-id attributes.
     *
     * data-id is registered only for this call via a post-create config hook,
     * so it does not leak into any other purifier profile.
     */
    public function clean(string $html): string
    {
        return Purifier::clean($html, 'notes', function (HTMLPurifier_Config $config): void {
            $definition = $config->maybeGetRawHTMLDefinition();

            if ($definition !== null) {
                $definition->addAttribute('span', 'data-id', 'Text');
            }
        });
    }
}
