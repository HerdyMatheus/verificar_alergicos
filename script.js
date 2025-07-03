async function verificarProduto() {
  const codigo = document.getElementById("codigo").value.trim();
  const resultadoDiv = document.getElementById("resultado");

  if (!codigo) {
    resultadoDiv.innerHTML = `<div class="alert alert-warning">Por favor, digite um código ou nome do produto.</div>`;
    return;
  }

  resultadoDiv.innerHTML = `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Carregando...</span></div>`;

  try {
    let nomeProduto = codigo;
    const ehGtin = /^\d{8,14}$/.test(codigo);

    if (ehGtin) {
      const respostaGtin = await fetch("backend_gtin.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ gtin: codigo })
      });

      const dadosGtin = await respostaGtin.json();

      if (dadosGtin.erro) {
        resultadoDiv.innerHTML = `
          <div class='alert alert-danger'>❌ Código de barras não encontrado.<br>${dadosGtin.sugestao}</div>
        `;
        return;
      }

      nomeProduto = dadosGtin.nome;
    }

    const respostaIA = await fetch("backend_gemini.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ produto: nomeProduto })
    });

    const dadosIA = await respostaIA.json();

    if (dadosIA.resposta) {
      resultadoDiv.innerHTML = `
        <div class='alert alert-secondary'><strong>🔎 Nome do produto:</strong> ${nomeProduto}</div>
        <div class='alert alert-info'><strong>💡 Resposta da IA:</strong><br>${dadosIA.resposta}</div>
      `;
    } else {
      resultadoDiv.innerHTML = `
        <div class='alert alert-secondary'><strong>🔎 Nome do produto:</strong> ${nomeProduto}</div>
      `;

      const alergias = ['lactose', 'ovo', 'aveia', 'corantes', 'frutos_do_mar'];
      alergias.forEach(key => {
        if (dadosIA[key]) {
          let cor = 'amarelo';
          if (dadosIA[key].toLowerCase().includes('não')) cor = 'verde';
          else if (dadosIA[key].toLowerCase().includes('sim')) cor = 'vermelho';

          const label = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
          resultadoDiv.innerHTML += `
            <div class="alergeno-card ${cor}">
              <strong>${label}:</strong><br>
              ${dadosIA[key]}
            </div>
          `;
        }
      });
    }

    // Etapa Imagem
    try {
      const imagemResponse = await fetch("backend_google_image.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ produto: nomeProduto })
      });

      const dadosImagem = await imagemResponse.json();

      if (dadosImagem.imagem) {
        resultadoDiv.innerHTML += `
          <div class='mt-3'>
            <strong>🖼️ Imagem real encontrada no Google:</strong><br>
            <img src="${dadosImagem.imagem}" alt="Imagem do Produto" class="img-fluid rounded border" style="max-height: 300px;">
          </div>
        `;
      } else {
        resultadoDiv.innerHTML += `<div class='mt-3 alert alert-warning'>⚠️ Nenhuma imagem encontrada no Google.</div>`;
      }
    } catch (imgErro) {
      console.warn("Erro ao buscar imagem:", imgErro);
    }

  } catch (erro) {
    resultadoDiv.innerHTML = `<div class="alert alert-danger">❌ Erro inesperado: ${erro.message}</div>`;
  }
}
