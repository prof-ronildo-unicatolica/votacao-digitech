<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AcaoAuditoria;
use App\Http\Controllers\Controller;
use App\Models\Eleicao;
use App\Support\Auditoria;
use App\Support\CorrenteDeHashes;
use App\Support\ResultadoDaEleicao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * H10 · Resultado. Antes do fim da votação, só o comparecimento. Depois, a apuração, o CSV e a
 * ata para imprimir ou salvar em PDF (D26). Os três leem `ResultadoDaEleicao`.
 */
class ResultadoController extends Controller
{
    public function index(Request $request): View
    {
        $eleicoes = Eleicao::orderByDesc('votacao_fecha_em')->get(['id', 'titulo', 'ativa', 'votacao_fecha_em']);
        // A escolhida na lista; senão a ativa; senão a que encerrou por último.
        $eleicao = $eleicoes->firstWhere('id', $request->query('eleicao'))
            ?? $eleicoes->firstWhere('ativa', true)
            ?? $eleicoes->first();
        $eleicao = $eleicao ? Eleicao::find($eleicao->id) : null;

        $resultado = null;
        if ($eleicao && ResultadoDaEleicao::liberado($eleicao)) {
            $resultado = ResultadoDaEleicao::apurar($eleicao);
            Auditoria::registrar(AcaoAuditoria::ResultadoConsultado, $eleicao, eleicaoId: $eleicao->id);
        }

        return view('admin.resultado.index', [
            'eleicoes' => $eleicoes,
            'eleicao' => $eleicao,
            'resultado' => $resultado,
            'comparecimento' => $eleicao ? ResultadoDaEleicao::comparecimento($eleicao) : null,
        ]);
    }

    public function csv(Eleicao $eleicao): Response|RedirectResponse
    {
        if (! ResultadoDaEleicao::liberado($eleicao)) {
            return $this->bloqueado($eleicao);
        }

        $conteudo = $this->montarCsv(ResultadoDaEleicao::apurar($eleicao));
        Auditoria::registrar(AcaoAuditoria::AtaExportada, $eleicao, ['formato' => 'csv', 'sha256' => hash('sha256', $conteudo)], $eleicao->id);

        return response($conteudo, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ata-'.str($eleicao->titulo)->slug().'.csv"',
        ]);
    }

    /** A ata pronta para imprimir. O navegador salva em PDF ("Imprimir" → "Salvar como PDF"). */
    public function ata(Eleicao $eleicao): Response|RedirectResponse
    {
        if (! ResultadoDaEleicao::liberado($eleicao)) {
            return $this->bloqueado($eleicao);
        }

        // A cabeça da corrente de hashes vai impressa: âncora fora do banco (D27).
        $html = view('admin.resultado.ata', ['resultado' => ResultadoDaEleicao::apurar($eleicao), 'emitida' => now(), 'cabeca' => CorrenteDeHashes::cabeca()])->render();
        Auditoria::registrar(AcaoAuditoria::AtaExportada, $eleicao, ['formato' => 'pdf', 'sha256' => hash('sha256', $html)], $eleicao->id);

        return response($html);
    }

    private function bloqueado(Eleicao $eleicao): RedirectResponse
    {
        $quando = $eleicao->votacao_fecha_em
            ? 'depois que a votação encerrar, em '.$eleicao->votacao_fecha_em->format('d/m/Y \à\s H:i')
            : 'depois que a votação encerrar. Cadastre as datas da eleição';

        return redirect()->route('admin.resultado', ['eleicao' => $eleicao->id])
            ->withErrors(['geral' => "A ata só pode ser exportada {$quando}."]);
    }

    /** Ata em CSV (";" e BOM, para o Excel abrir com acentos): cabeçalho, votos e eleitos. */
    private function montarCsv(ResultadoDaEleicao $r): string
    {
        $a = $r->apuracao;
        $num = fn (float $v) => number_format($v, 1, ',', '');
        $vencedora = $r->vencedora();

        $linhas = [
            ['Eleição', $r->eleicao->titulo],
            ['Votação', $r->eleicao->votacao_abre_em?->format('d/m/Y H:i').' a '.$r->eleicao->votacao_fecha_em?->format('d/m/Y H:i')],
            ['Aptos', $a->aptos],
            ['Votaram', $a->votaram],
            ['Participação (%)', $num($a->participacao())],
            ['Votos válidos', $a->validos],
            ['Total de votos', $a->total],
            ['Auditoria (eventos)', CorrenteDeHashes::cabeca()->ultima_sequencia],
            ['Auditoria (último hash)', CorrenteDeHashes::cabeca()->ultimo_hash],
            ['Resultado', match (true) {
                (bool) $vencedora => "Chapa {$vencedora->numero} · {$vencedora->nome}",
                $a->empate() => 'Empate: '.$r->empatadas()->map(fn ($c) => "chapa {$c->numero}")->join(', ', ' e '),
                default => 'Sem votos válidos',
            }],
            [],
            ['Opção', 'Votos', '% do total', '% dos válidos'],
        ];
        foreach ($r->linhas() as $l) {
            $linhas[] = [$l['rotulo'], $l['votos'], $num($l['total']), $l['validos'] === null ? '' : $num($l['validos'])];
        }
        if ($r->eleitos->isNotEmpty()) {
            $linhas[] = [];
            $linhas[] = ['Cargo', 'Eleito', 'Matrícula'];
            foreach ($r->eleitos as $c) {
                $linhas[] = [$c->cargo->titulo, $c->eleitor->nome, $c->eleitor->matricula];
            }
        }

        $saida = fopen('php://temp', 'r+');
        fwrite($saida, "\u{FEFF}");
        foreach ($linhas as $linha) {
            fputcsv($saida, $linha, ';', '"', '');
        }
        rewind($saida);

        return stream_get_contents($saida);
    }
}
