const AureliusIA = {
    conhecimento: {
        identidade: "🧠 **Saudações Académicas! Eu sou o Aurélius IA**, o teu mentor pedagógico digital e assistente ultra-inteligente oficial da Academia Aurélius. O meu cérebro foi treinado para te transformar num verdadeiro **matreco** (crânio nos estudos) aqui no Huambo! Estou pronto para desatar qualquer nó sobre matrículas, localização, programação ou matemática.",
        
        instituicao: {
            direcao: "📍 **Localização e Modos de Estudo (Huambo):**\nEstamos sediados na Província do **Huambo, Angola**. Como sabemos que cada estudante tem o seu ritmo, operamos em dois formatos poderosos:\n• 🏫 **Formação Presencial:** Aulas reativas nas nossas salas equipadas com tecnologia moderna.\n• 🏠 **Formação Domiciliar (Explicador em Casa):** O professor vai fisicamente até à tua residência para te dar todo o apoio necessário com total foco e exclusividade.",
            
            como_matricular: "📋 **Manual de Inscrição e Matrícula Imperial:**\n1. Acede à nossa página principal (`Principal.html`).\n2. Escolhe a tua categoria-alvo: **Ensino Regular, Superior, Cursos de Férias, Cadeiras Isoladas ou Preparatórios**.\n3. Preenche o formulário centralizado com os teus dados de contacto reais.\n4. ⏳ **O Segredo do Fluxo:** Após submeteres, o teu estado ficará como *Pendente*. Não te preocupes, os nossos professores avaliam a tua pauta na nuvem do Render e aprovam a tua entrada na turma ativa. Logo a seguir, os teus manuais em PDF e o Chat abrem-se de forma automática!"
        },

        disciplinas: {
            dois_mais_dois: "📐 **Análise Matemática Crítica:**\n**2 + 2 é igual a 4!** \n*Explicação Sábia:* Na aritmética pura, estamos a juntar duas unidades discretas a outras duas. Se aplicarmos isto à computação, `2 + 2 === 4` é uma operação de tipo inteiro executada na CPU do servidor do Render em menos de um nanossegundo!",
            
            matematica: "📐 **A Sabedoria dos Números (Mentoria de Matemática):**\nPara dominares qualquer equação complicada, o teu primeiro objetivo deve ser **isolar a incógnita (x)**. \n*A Regra de Ouro:* Qualquer termo que mude de lado na igualdade tem de inverter a sua operação mecânica:\n• O que está a **somar** passa a **subtrair**.\n• O que está a **multiplicar** passa a **dividir**.\n👉 *Pratica muito, erra no rascunho e serás o maior matreco da tua turma!*",
            
            programacao: "💻 **Engenharia de Software & Segurança (Dica de Programação):**\nNo desenvolvimento web com **PHP e MySQL**, as credenciais da tua base de dados (como as da Aiven) são o coração do teu sistema. \n*Aviso de Poder:* **Nunca as escrevas diretamente no teu `conexao.php`**. Se o teu repositório for público no GitHub, qualquer pessoa pode invadir o teu banco. \n*Como Resolver:* Usa sempre `getenv('DB_PASSWORD')` e configura as variáveis de ambiente de forma segura dentro do painel do Render. Código limpo é código seguro!",
            
            falta_nota: "📊 **Sobre Notas, Pautas e Cadernetas:**\nAs notas de frequência (**N1, N2, N3**) e faltas são lançadas diretamente pelo professor na pauta digital. A média de aprovação em Angola é de **9.5 Valores**. Se a tua média for igual ou superior a 9.5, o teu Estatuto muda reativamente para **Aprovado** no teu painel do aluno!"
        },

        giras_contextuais: "💡 *Dica do Ndengue:* Lembra-te que nos estudos não há mambos impossíveis. Se fores disciplinado e leres os manuais na tua prateleira todos os dias, vais bazar para o topo das notas!"
    },

    processarMensagem: function(inputUsuario) {
        let texto = inputUsuario.toLowerCase().trim();
        let resposta = "";

        // Remover acentos para normalizar a pesquisa e evitar falhas de interpretação
        texto = texto.normalize("NFD").replace(/[\u0300-\u036f]/g, "");

        // 1. SAUDAÇÃO INTELIGENTE (Detecta variações amplas e saúda de forma sábia)
        if (texto.match(/(ola|oi|bom dia|boa tarde|boa noite|viva|saudacoes|salve)/)) {
            if (texto.match(/(como esta|tudo bem|como estais|tudo fixe|como vais)/)) {
                resposta += "👋 **Saudações, meu caro estudante!** Eu estou ultra-inteligente, poderosa e pronta para estalar o crânio nos livros contigo hoje! ";
            } else {
                resposta += "👋 **Viva! Saudações Académicas!** Que mambos queres dominar hoje na nossa academia? ";
            }
        }

        // 2. CONTEXTO: Inscrição, Matrícula, Cursos, Entrar, Cadastro
        if (texto.match(/(matricular|matricula|inscricao|inscrever|como faco|como faço|entrar|cadastrar|estudar na academia)/)) {
            resposta += (resposta ? "\n\n" : "") + this.conhecimento.instituicao.como_matricular;
        }

        // 3. CONTEXTO: Localização, Endereço, Onde Fica, Huambo, Rua, Direção, Presencial, Domicílio
        if (texto.match(/(direcao|localizacao|onde fica|endereco|huambo|onde estao|presencial|domicilio|casa)/)) {
            resposta += (resposta ? "\n\n" : "") + this.conhecimento.instituicao.direcao;
        }

        // 4. CONTEXTO: Matemática, Contas, Equações, Cálculos, 2+2
        if (texto.match(/(2\s*\+\s*2|dois mais dois|quanto e 2)/)) {
            resposta += (resposta ? "\n\n" : "") + this.conhecimento.disciplinas.dois_mais_dois;
        } else if (texto.match(/(matematica|calculo|conta|equacao|incognita|algebra)/)) {
            resposta += (resposta ? "\n\n" : "") + this.conhecimento.disciplinas.matematica;
        }

        // 5. CONTEXTO: Códigos, Programação, PHP, GitHub, Servidor, MySQL, Banco de Dados, Render
        if (texto.match(/(codigo|programacao|php|mysql|banco|server|render|github|desenvolvimento|web)/)) {
            resposta += (resposta ? "\n\n" : "") + this.conhecimento.disciplinas.programacao;
        }

        // 6. CONTEXTO: Notas, Pautas, Caderneta, Média, Faltas, N1, N2, N3, Aprovado
        if (texto.match(/(nota|pauta|caderneta|media|falta|n1|n2|n3|aprovado|reprovado|estatuto)/)) {
            resposta += (resposta ? "\n\n" : "") + this.conhecimento.disciplinas.falta_nota;
        }

        // 7. CONTEXTO: Gírias Angolanas isoladas
        if (texto.match(/(mambo|bazar|matreco|ndengue|fixe)/) && !resposta.includes("📋") && !resposta.includes("📍")) {
            resposta += (resposta ? "\n\n" : "") + "🇦🇴 **Dicionário Cultural Aurélius:** Vejo que dominas a nossa gerência! " + this.conhecimento.giras_contextuais;
        }

        // 8. RESPOSTA DE SALVAGUARDA (Fallback pedagógico caso a pergunta seja fora do comum)
        if (resposta.trim() === "" || resposta.startsWith("👋 **Viva!")) {
            if (resposta.trim() === "") {
                resposta = this.conhecimento.identidade;
            }
            resposta += "\n\n**O conhecimento é poder! Pergunta-me com total liberdade sobre:**\n" +
                        "• 📋 *'Como faço a minha matrícula ou inscrição?'*\n" +
                        "• 📍 *'Qual é o endereço ou direção da academia no Huambo?'*\n" +
                        "• 📐 *'Dá-me uma mentoria de matemática ou como resolver contas.'*\n" +
                        "• 💻 *'Como proteger as chaves do banco de dados no PHP e Render?'*\n" +
                        "• 📊 *'Qual é a média de aprovação e como funcionam as notas?'*";
        }

        // 🛡️ FILTRO ABSOLUTO CONTRA ESTOUROS DE LAYOUT NO TELEMÓVEL
        resposta = resposta.replace(/<br\s*\/?>/gi, " ");
        resposta = resposta.replace(/<hr\s*\/?>/gi, " --- ");
        resposta = resposta.replace(/<\/?[^>]+(>|\$)/g, ""); // Garante que nenhuma tag HTML parta o ecrã

        return resposta.trim();
    }
};