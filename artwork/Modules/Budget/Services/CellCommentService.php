<?php

namespace Artwork\Modules\Budget\Services;

use Artwork\Modules\Budget\Models\CellComment;
use Artwork\Modules\Budget\Repositories\CellCommentRepository;

class CellCommentService
{
    public function __construct(
        private readonly CellCommentRepository $cellCommentRepository
    ) {
    }

    public function delete(CellComment $cellComment): void
    {
        $this->cellCommentRepository->delete($cellComment);
    }
}