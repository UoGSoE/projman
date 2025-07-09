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
        return $this->updated_at->diffInSeconds($this->created_at) > 10;
    }
}
