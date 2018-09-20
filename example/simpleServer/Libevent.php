<?php
class Libevent
{

    public function __construct()
    {
        var_dump(get_extension_funcs("libevent"));
        $this->base = event_base_new();
    }


}