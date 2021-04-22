<?php

namespace StoreKeeper\ApiWrapper\Iterator;

class ListCallByIdPaginatedIterator extends ListCallByIdIterator
{
    use PaginatedIteratorTrait;

    public function next()
    {
        parent::next();
        if (!parent::valid()) {
            // end of the array or empty
            $this->onNextInvalid();
        }
    }
}
