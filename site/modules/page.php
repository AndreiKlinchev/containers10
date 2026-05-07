<?php

class Page
{
    private $template;

    public function __construct($template)
    {
        $this->template = $template;
    }

    public function Render($data)
    {
        $page = file_get_contents($this->template);

        foreach ($data as $key => $value) {
            $page = str_replace('{{' . $key . '}}', $value, $page);
        }

        return $page;
    }
}
