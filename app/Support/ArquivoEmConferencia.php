<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * O arquivo enviado que espera a conferência do admin (D19). Fica em
 * storage/app/private/importacoes e o caminho fica na sessão de quem enviou: nada vai para
 * o banco antes de confirmar. Enviar outro arquivo, cancelar ou confirmar apaga o anterior.
 */
class ArquivoEmConferencia
{
    /** @param string $chave chave na sessão, uma por tipo de importação (ex.: `importacao_chapas`) */
    public function __construct(private readonly string $chave) {}

    /** @param array<string, mixed> $extra dados para conferir depois (ex.: id da eleição) */
    public function guardar(string $conteudo, string $nomeOriginal, array $extra = []): void
    {
        $this->descartar();

        $caminho = 'importacoes/'.Str::slug($this->chave).'-'.Str::uuid().'.csv';
        Storage::disk('local')->put($caminho, $conteudo);

        session([$this->chave => ['arquivo' => $caminho, 'nome' => $nomeOriginal] + $extra]);
    }

    /** Dados do arquivo em conferência, ou null se não há (ou se ele sumiu do disco). */
    public function dados(): ?array
    {
        $dados = session($this->chave);

        if (! $dados || ! Storage::disk('local')->exists($dados['arquivo'])) {
            $this->descartar();

            return null;
        }

        return $dados;
    }

    public function conteudo(): ?string
    {
        $dados = $this->dados();

        return $dados ? Storage::disk('local')->get($dados['arquivo']) : null;
    }

    public function descartar(): void
    {
        if ($arquivo = session($this->chave.'.arquivo')) {
            Storage::disk('local')->delete($arquivo);
        }
        session()->forget($this->chave);
    }
}
