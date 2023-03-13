<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class GeneralResponseCollection extends ResourceCollection
{
    private $action, $message, $data;
    public function __construct($data, $message, $action)
    {
        $this->data = $data;
        $this->message = $message;
        $this->action = $action;
    }
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable
     */
    public function toArray($request)
    {
        return [
            'action' => $this->action,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
