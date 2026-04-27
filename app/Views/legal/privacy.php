<?php /** @var string $title */ ?>

<div class="min-h-screen bg-gray-900 text-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto">
        <div class="bg-gray-800 rounded-xl shadow-lg p-8">
            <h1 class="text-2xl font-bold text-white mb-6 flex items-center gap-3">
                <i data-lucide="shield" class="w-7 h-7 text-green-400"></i>
                Política de Privacidade
            </h1>

            <p class="text-sm text-gray-400 mb-6">Última atualização: <?= date('d/m/Y') ?></p>

            <div class="space-y-6 text-gray-300 leading-relaxed">
                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">1. Informações que Coletamos</h2>
                    <p>Coletamos informações que você fornece diretamente: nome, e-mail, dados de empresas e leads. Também coletamos dados de uso (logs de acesso, IP, navegador) para segurança e melhoria do serviço.</p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">2. Como Utilizamos seus Dados</h2>
                    <ul class="list-disc list-inside space-y-1 ml-4">
                        <li>Prestar e melhorar os serviços do CRM</li>
                        <li>Comunicações sobre sua conta e atualizações do serviço</li>
                        <li>Prevenção de fraudes e abusos</li>
                        <li>Cumprimento de obrigações legais</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">3. Compartilhamento de Dados</h2>
                    <p>Não vendemos, alugamos ou compartilhamos seus dados pessoais com terceiros para fins de marketing. Podemos compartilhar dados com:</p>
                    <ul class="list-disc list-inside space-y-1 ml-4 mt-2">
                        <li>Provedores de infraestrutura (hospedagem, banco de dados)</li>
                        <li>Autoridades legais quando exigido por lei</li>
                        <li>Outros usuários da mesma conta (dados compartilhados da empresa)</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">4. Segurança dos Dados</h2>
                    <p>Implementamos medidas de segurança técnicas e organizacionais para proteger seus dados:</p>
                    <ul class="list-disc list-inside space-y-1 ml-4 mt-2">
                        <li>Criptografia HTTPS/TLS em todas as comunicações</li>
                        <li>Hash de senhas com bcrypt (cost 12)</li>
                        <li>Proteção contra CSRF, XSS e SQL Injection</li>
                        <li>Rate limiting e monitoramento de acessos</li>
                        <li>Backup automático e criptografado</li>
                    </ul>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">5. Retenção de Dados</h2>
                    <p>Seus dados são mantidos enquanto sua conta estiver ativa. Após exclusão da conta, os dados são retidos por 30 dias e depois removidos permanentemente. Dados de auditoria (logs de acesso) são mantidos por 1 ano para fins de segurança.</p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">6. Seus Direitos (LGPD)</h2>
                    <p>Nos termos da Lei Geral de Proteção de Dados, você tem direito a:</p>
                    <ul class="list-disc list-inside space-y-1 ml-4 mt-2">
                        <li><strong>Acessar</strong> — solicitar cópia dos seus dados pessoais</li>
                        <li><strong>Corrigir</strong> — solicitar correção de dados incompletos ou inexatos</li>
                        <li><strong>Excluir</strong> — solicitar exclusão dos seus dados pessoais</li>
                        <li><strong>Exportar</strong> — receber seus dados em formato estruturado</li>
                        <li><strong>Revogar consentimento</strong> — retirar seu consentimento a qualquer momento</li>
                    </ul>
                    <p class="mt-2">Para exercer seus direitos, faça login e acesse Exportar Dados ou Excluir Conta nas configurações.</p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">7. Cookies</h2>
                    <p>Utilizamos cookies essenciais para funcionamento do sistema (sessão, autenticação, CSRF). Não utilizamos cookies de rastreamento de terceiros ou para publicidade.</p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">8. Transferência Internacional</h2>
                    <p>Seus dados são processados e armazenados em servidores no Brasil. Em caso de necessidade de transferência internacional, serão adotadas cláusulas contratuais padrão e outras salvaguardas legais.</p>
                </section>

                <section>
                    <h2 class="text-lg font-semibold text-white mb-2">9. Contato</h2>
                    <p>Para questões de privacidade, entre em contato pelo e-mail: <span class="text-blue-400">privacidade@prospec.crm</span></p>
                </section>
            </div>

            <div class="mt-8 pt-6 border-t border-gray-700 flex items-center justify-between">
                <a href="/terms" class="text-blue-400 hover:underline text-sm">← Termos de Uso</a>
                <a href="/login" class="text-gray-400 hover:text-gray-200 text-sm flex items-center gap-1">
                    <i data-lucide="log-in" class="w-4 h-4"></i> Ir para Login
                </a>
            </div>
        </div>
    </div>
</div>