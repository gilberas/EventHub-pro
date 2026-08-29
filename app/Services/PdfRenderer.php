<?php

declare(strict_types=1);

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;

class PdfRenderer
{
    protected string $viewPath;

    protected array $data = [];

    protected ?string $paperSize = 'letter';

    protected ?string $orientation = 'portrait';

    public function view(string $viewPath): static
    {
        $this->viewPath = $viewPath;

        return $this;
    }

    public function data(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function paper(string $size, string $orientation = 'portrait'): static
    {
        $this->paperSize = $size;
        $this->orientation = $orientation;

        return $this;
    }

    public function render(): DomPDF
    {
        return Pdf::loadView($this->viewPath, $this->data)
            ->setPaper($this->paperSize, $this->orientation);
    }

    public function download(string $filename): mixed
    {
        return $this->render()->download($filename);
    }

    public function save(string $path): string
    {
        $this->render()->save($path);

        return $path;
    }
}
