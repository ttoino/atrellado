<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\CursorPaginator;

class ThreadResource extends JsonResource
{
    protected ?CursorPaginator $comments = null;

    /**
     * Embed a page of comments, keyed by data/links/meta.
     */
    public function withComments(CursorPaginator $comments): static
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...$this->resource->toArray(),
            'editable' => $request->user()?->can('update', $this->resource),
            'comments' => $this->when(
                $this->comments !== null,
                fn () => ThreadCommentResource::collection($this->comments)->response($request)->getData(true)
            ),
        ];
    }
}
