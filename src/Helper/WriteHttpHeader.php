<?php declare(strict_types=1);

namespace Sanjos\Helper;

class WriteHttpHeader {

    private $header     = "";
    private $headerArr  = [];

    public function add(string $key, string $value) : self
    {
        $this->headerArr[$key] = $value;
        return $this;
    }

    public function addLine(string $line) : self
    {
        $this->header .= sprintf("%s\r\n", $line);
        return $this;
    }

    public function render() : string
    {
        foreach($this->headerArr as $key => $value)
        {
            $this->header .= sprintf("%s: %s\r\n", $key, $value);
        }
        return $this->header . "\r\n";
    }
}