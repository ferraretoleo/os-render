from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]


def content(name: str) -> str:
    path = ROOT / name
    assert path.is_file(), f"arquivo ausente: {name}"
    return path.read_text(encoding="utf-8")


def test_pages_and_security_contracts():
    listing = content("orcamentos.php")
    form = content("orcamento_form.php")
    detail = content("orcamento_detalhe.php")
    download = content("orcamento_arquivo.php")
    decision = content("orcamento_decisao.php")
    deletion = content("orcamento_excluir.php")

    assert "status" in listing and "fornecedor" in listing and "data_inicio" in listing
    assert "OrcamentoArquivo::validar" in form
    assert "storageUpload" in form and "storageDelete" in form
    assert "orcamento_historico" in detail
    assert "storageDownload" in download and "Content-Disposition" in download
    assert "requireAdmin()" in decision and "validateCsrf()" in decision
    assert "REQUEST_METHOD" in decision and "POST" in decision
    assert "requireAdmin()" in deletion and "storageDelete" in deletion


def test_minimal_navigation_integration():
    layout = content("_layout_top.php")
    dashboard = content("dashboard.php")

    assert 'href="orcamentos.php"' in layout
    assert "pendingBudgetCount" in dashboard
    assert 'href="orcamentos.php?status=pendente"' in dashboard


if __name__ == "__main__":
    test_pages_and_security_contracts()
    test_minimal_navigation_integration()
    print("OK: contratos do fluxo de orçamentos validados")
