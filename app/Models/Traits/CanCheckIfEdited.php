<?php

namespace App\Models\Traits;

trait CanCheckIfEdited
{
    /**
     * Check if the model has been edited.
     * (was it updated after it was created?)
     *
     * @return bool
     */
    public function hasBeenEdited(): bool
    {
        return $this->created_at->diffInSeconds($this->updated_at) > 10;
    }
}
