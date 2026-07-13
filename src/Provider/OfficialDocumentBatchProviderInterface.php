<?php

interface OfficialDocumentBatchProviderInterface
{
    /**
     * Fetch multiple official documents by their parliament-native document
     * numbers in as few upstream requests as possible. Implementations must
     * validate the number format (it is parliament-specific).
     *
     * @param string[] $documentNumbers
     * @return array ApiResponse envelope; on success `data` is a numeric array
     *               of document objects in the same shape as the single fetch()
     *               response, each carrying an additional `documentNumber`
     *               field for correlating results to the requested numbers.
     *               Numbers with no match simply have no item.
     */
    public function fetchBatch(array $documentNumbers): array;
}
