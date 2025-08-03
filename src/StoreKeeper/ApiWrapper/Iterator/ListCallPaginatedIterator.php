<?php

namespace StoreKeeper\ApiWrapper\Iterator;

class ListCallPaginatedIterator extends ListCallIterator
{
    use PaginatedIteratorTrait;

    public function next(): void
    {
        parent::next();
        if (!parent::valid()) {
            // end of the array or empty
            $this->onNextInvalid();
        }
    }
}
