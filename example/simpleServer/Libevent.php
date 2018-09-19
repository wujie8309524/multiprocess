<?php
class Libevent
{
    public function __construct()
    {
        $this->base = event_base_new();
    }


}