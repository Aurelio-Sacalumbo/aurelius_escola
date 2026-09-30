/**
 * 🧠 ALANA IA - ASSISTENTE ULTRAINTELIGENTE DA ACADEMIA AURÉLIUS
 * Suporte Avançado, Processamento de Frases Complexas, Endereço e Matrículas
 */

const AureliusIA = {
    conhecimento: {
        identidade: "Olá! Viva! Sou o Aurélius IA, seu assistente oficial da Academia Aurélius. Estou aqui para te ajudar com tudo sobre os nossos cursos, suporte e localização no Huambo!",
        
        // 📍 INFORMAÇÕES DE DIRECÇÃO E INSCRIÇÃO EXCLUSIVAS
        instituicao: {
            direcao: "📍 **Direção da Academia Aurélius:** Estamos localizados na Província do Huambo, Angola. Oferecemos tanto **Formação Presencial** nas nossas salas equipadas como **Formação Domiciliar** (o professor vai até à tua casa para te dar todo o apoio necessário).",
            como_matricular: "📋 **Como fazer a matrícula:**\n1. Vai à nossa página principal (`Principal.html`).\n2. Escolhe o teu fluxo (Ensino Regular, Superior, Férias, Cadeiras Isoladas ou Preparatórios).\n3. Preenche o formulário com os teus dados.\n4. **Nota Importante:** A tua inscrição ficará com o estado *Pendente*. Um dos nossos professores irá analisar o teu curso e aprovar a tua entrada na turma para poderes começar as aulas com total segurança!"
        },

        girias_angola: {
            "mambo": "Coisa, assunto ou situação. Como estão os mambos dos teus estudos?",
            "bazar": "Ir embora ou avançar. Vamos bazar para os cadernos e computadores?",
            "matreco": "Estudante focado, crânio e inteligente.",
            "ndengue": "Estudante mais jovem ou principiante."
        },

        disciplinas: {
            "dois_mais_dois": "📐 **2 + 2 é igual a 4!** Se precisares de ajuda para desatar nós em equações complicadas ou lógica de programação, conta comigo!",
            "matematica": "📐 **Dica de Matemática:** Para dominares as equações, lembra-se de isolar a incógnita (x). Qualquer termo que mude de lado na igualdade inverte o seu sinal (o que soma vira subtração, o que multiplica vira divisão)!",
            "programacao": "💻 **Dica de Programação:** No desenvolvimento web com PHP, nunca coloques credenciais diretamente no código do teu `conexao.php`. Usa variáveis de ambiente no Render (`getenv`) para proteger o teu banco de dados de invasões!"
        }
    },

    processarMensagem: function(inputUsuario) {
        let texto = inputUsuario.toLowerCase().trim();
        let resposta = "";

        // 1. Saudação Inteligente Integrada (Detecta variações como "como estais", "tudo bem")
        if (texto.includes("ola") || texto.includes("oi") || texto.includes("bom dia") || texto.includes("boa tarde") || texto.includes("boa noite") || texto.includes("viva")) {
            if (texto.includes("como esta") || texto.includes("tudo bem") || texto.includes("como estais")) {
                resposta += "👋 Olá! Eu estou ótima e pronta para estalar o crânio nos estudos contigo! ";
            } else {
                resposta += "👋 Olá! Viva! ";
            }
        }

        // 2. Inteligência Avançada: Identificar perguntas sobre "Como se matricular" ou "Inscrição"
        if (texto.includes("como faço") || texto.includes("como faco") || texto.includes("matricular") || texto.includes("inscri") || texto.includes("matrícula")) {
            resposta += "\n\n" + this.conhecimento.instituicao.como_matricular;
        }

        // 3. Inteligência Avançada: Identificar perguntas sobre "Direção", "Onde fica", "Localização" ou "Endereço"
        if (texto.includes("direção") || texto.includes("direcao") || texto.includes("onde fica") || texto.includes("localiz") || texto.includes("endereco") || texto.includes("endereço") || texto.includes("huambo")) {
            resposta += "\n\n" + this.conhecimento.instituicao.direcao;
        }

        // 4. Detecção de Matemática e Contas Rápidas
        if (texto.includes("2+2") || texto.includes("2 + 2") || texto.includes("dois mais dois")) {
            resposta += "\n\n" + this.conhecimento.disciplinas.dois_mais_dois;
        } else if (texto.includes("matematica") || texto.includes("calculo") || texto.includes("conta")) {
            resposta += "\n\n" + this.conhecimento.disciplinas.matematica;
        }

        // 5. Detecção de Programação e Códigos
        if (texto.includes("codigo") || texto.includes("código") || texto.includes("programacao") || texto.includes("php")) {
            resposta += "\n\n" + this.conhecimento.disciplinas.programacao;
        }

        // 6. Resposta de Salvaguarda Dinâmica (Se o utilizador falar algo fora do radar)
        if (resposta.trim() === "" || resposta === "👋 Olá! Viva! ") {
            resposta = this.conhecimento.identidade + "\n\nPergunta-me coisas como:\n• *'Como faço a minha matrícula?'*\n• *'Qual é a vossa direção?'*\n• *'Dá-me uma dica de programação ou matemática'*\n• *'Quanto é 2+2?'*";
        }

        // 🛡️ Filtro contra Estouros de Layout (Text Overflow) no telemóvel
        resposta = resposta.replace(/<br\s*\/?>/gi, " ");
        resposta = resposta.replace(/<hr\s*\/?>/gi, " --- ");
        resposta = resposta.replace(/<\/?[^>]+(>|\$)/g, ""); 

        return resposta.trim();
    }
};