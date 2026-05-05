<?php

interface OfficialDocumentProviderInterface
{
    /**
     * Fetch and normalise an official document from the parliament's source.
     *
     * The handler has already performed parliament-agnostic input validation
     * and (when needed) resolved an OPTV `id` to the parliament's native
     * `documentID`. The provider receives `$input` with whichever of
     * `documentID` and `sourceURI` are populated.
     *
     * Returns a full ApiResponse-style envelope (success or error). The
     * handler returns this directly to the caller.
     *
     * @param array<string,mixed> $input Request input, possibly with `documentID` resolved.
     * @return array<string,mixed> Full ApiResponse-style envelope.
     */
    public function fetch(array $input): array;
}
