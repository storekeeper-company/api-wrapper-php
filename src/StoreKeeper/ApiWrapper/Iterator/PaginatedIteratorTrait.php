<?php


namespace StoreKeeper\ApiWrapper\Iterator;


trait PaginatedIteratorTrait
{

    /**
     * @var int
     */
    protected $per_page = 100;
    /**
     * @var int
     */
    protected $start = 0;

    /**
     * @return int
     */
    abstract function getCount(): int;

    /**
     * @param bool $executed
     *
     * @return mixed
     */
    abstract protected function setExecuted(bool $executed): void ;
    /**
     * @return bool
     */
    public function maybeHasMore(): bool
    {
        return $this->getCount() > 0 && $this->per_page <= $this->getCount();
    }
    /**
     * @return int
     */
    public function getStart(): int
    {
        return $this->start;
    }

    public function onNextInvalid()
    {
        if( $this->maybeHasMore() ){
            // there was something lets try again
            $this->start += $this->per_page;
            $this->setExecuted(false);
            // no re-calling of next cos it will skip the first element than
            // self::valid() call will to the backend call
        }
    }
    /**
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->per_page;
    }

    /**
     * @param int $per_page
     */
    public function setPerPage(int $per_page): void
    {
        $this->per_page = $per_page;
    }
}
