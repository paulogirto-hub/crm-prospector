<?php
/**
 * Routes — Definição de rotas do Prospec CRM
 * 
 * Formato: Router::method('/uri', 'Controller@action', ['Middleware1', 'Middleware2']);
 */

use App\Core\Router;

// ══════════════════════════════════════
// Health & Readiness (MELH-008 / INFRA-19)
// ══════════════════════════════════════
Router::get('/health', 'HealthController@health');
Router::get('/ready', 'HealthController@ready');

// ══════════════════════════════════════
// Auth Routes
// ══════════════════════════════════════
Router::get('/login', 'AuthController@loginForm', ['GuestMiddleware']);
Router::post('/login', 'AuthController@login', ['GuestMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::get('/register', 'AuthController@registerForm', ['AuthMiddleware', 'AdminMiddleware']);
Router::post('/register', 'AuthController@register', ['AuthMiddleware', 'CsrfMiddleware', 'AdminMiddleware', 'RateLimitMiddleware']);

// Self-registration (public) — BUG-010
Router::get('/signup', 'AuthController@registerForm', ['GuestMiddleware']);
Router::post('/signup', 'AuthController@register', ['GuestMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::post('/logout', 'AuthController@logout', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::get('/profile', 'AuthController@profile', ['AuthMiddleware']);
Router::post('/profile', 'AuthController@updateProfile', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);

// ══════════════════════════════════════
// Dashboard
// ══════════════════════════════════════
Router::get('/', 'DashboardController@index', ['AuthMiddleware']);
Router::get('/dashboard', 'DashboardController@index', ['AuthMiddleware']);

// ══════════════════════════════════════
// Leads
// ══════════════════════════════════════
Router::get('/leads', 'LeadController@index', ['AuthMiddleware']);
Router::get('/leads/create', 'LeadController@create', ['AuthMiddleware']);
Router::post('/leads', 'LeadController@store', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::get('/leads/{id}', 'LeadController@show', ['AuthMiddleware']);
Router::get('/leads/{id}/edit', 'LeadController@edit', ['AuthMiddleware']);
Router::post('/leads/{id}', 'LeadController@update', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::put('/leads/{id}', 'LeadController@update', ['AuthMiddleware', 'CsrfMiddleware']);
Router::post('/leads/{id}/stage', 'LeadController@moveStage', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::post('/leads/{id}/activity', 'LeadController@addActivity', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::post('/leads/{id}/delete', 'LeadController@delete', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::delete('/leads/{id}', 'LeadController@delete', ['AuthMiddleware', 'CsrfMiddleware']);

// ══════════════════════════════════════
// Pipeline (Kanban)
// ══════════════════════════════════════
Router::get('/pipeline', 'PipelineController@index', ['AuthMiddleware']);
Router::post('/pipeline/move/{leadId}', 'PipelineController@move', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);

// ══════════════════════════════════════
// Prospecção (integrado com Prospector API)
// ══════════════════════════════════════
Router::get('/prospec', 'ProspecController@index', ['AuthMiddleware']);
Router::post('/prospec/search', 'ProspecController@search', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::get('/prospec/session/{id}', 'ProspecController@session', ['AuthMiddleware']);
Router::get('/prospec/session/{id}/status', 'ProspecController@sessionStatus', ['AuthMiddleware']);
Router::post('/prospec/enrich/{id}', 'ProspecController@enrich', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::post('/prospec/score/{id}', 'ProspecController@score', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::post('/prospec/analyze/{id}', 'ProspecController@analyze', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::post('/prospec/session/{id}/analyze-lead', 'ProspecController@analyzeLead', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::post('/prospec/import/{id}', 'ProspecController@import', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::post('/prospec/import-lead/{searchId}/{leadId}', 'ProspecController@importLead', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::post('/prospec/session/{id}/diagnose/{leadId}', 'ProspecController@diagnose', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::post('/prospec/session/{id}/analyze-market', 'ProspecController@analyzeMarketAction', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::get('/prospec/session/{id}/lead/{leadId}', 'ProspecController@leadDetail', ['AuthMiddleware']);
Router::get('/prospec/export/{id}', 'ProspecController@export', ['AuthMiddleware']);
Router::get('/prospec/history', 'ProspecController@history', ['AuthMiddleware']);

// ══════════════════════════════════════
// Templates
// ══════════════════════════════════════
Router::get('/templates', 'TemplateController@index', ['AuthMiddleware']);
Router::get('/templates/create', 'TemplateController@create', ['AuthMiddleware']);
Router::post('/templates', 'TemplateController@store', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::get('/templates/{id}/edit', 'TemplateController@edit', ['AuthMiddleware']);
Router::post('/templates/{id}', 'TemplateController@update', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::put('/templates/{id}', 'TemplateController@update', ['AuthMiddleware', 'CsrfMiddleware']);
Router::post('/templates/{id}/delete', 'TemplateController@delete', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::delete('/templates/{id}', 'TemplateController@delete', ['AuthMiddleware', 'CsrfMiddleware']);

// ══════════════════════════════════════
// Agenda (Tarefas)
// ══════════════════════════════════════
Router::get('/agenda', 'AgendaController@index', ['AuthMiddleware']);
Router::get('/agenda/create', 'AgendaController@create', ['AuthMiddleware']);
Router::post('/agenda', 'AgendaController@store', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::post('/agenda/{id}/complete', 'AgendaController@complete', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::post('/agenda/{id}/delete', 'AgendaController@delete', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);

// ══════════════════════════════════════
// Empresas
// ══════════════════════════════════════
Router::get('/companies', 'CompanyController@index', ['AuthMiddleware']);
Router::get('/companies/create', 'CompanyController@create', ['AuthMiddleware']);
Router::post('/companies', 'CompanyController@store', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::get('/companies/{id}', 'CompanyController@show', ['AuthMiddleware']);
Router::get('/companies/{id}/edit', 'CompanyController@edit', ['AuthMiddleware']);
Router::post('/companies/{id}', 'CompanyController@update', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::put('/companies/{id}', 'CompanyController@update', ['AuthMiddleware', 'CsrfMiddleware']);

// ══════════════════════════════════════
// Relatórios
// ══════════════════════════════════════
Router::get('/reports', 'ReportController@index', ['AuthMiddleware']);

// ══════════════════════════════════════
// Configurações (Admin)
// ══════════════════════════════════════
Router::get('/settings', 'SettingsController@index', ['AuthMiddleware']);
Router::get('/settings/team', 'SettingsController@team', ['AuthMiddleware', 'AdminMiddleware']);
Router::post('/settings/stage/create', 'SettingsController@createStage', ['AuthMiddleware', 'CsrfMiddleware', 'AdminMiddleware', 'RateLimitMiddleware']);
Router::post('/settings/stage/{id}', 'SettingsController@editStage', ['AuthMiddleware', 'CsrfMiddleware', 'AdminMiddleware', 'RateLimitMiddleware']);
Router::put('/settings/stage/{id}', 'SettingsController@editStage', ['AuthMiddleware', 'CsrfMiddleware', 'AdminMiddleware']);
Router::post('/settings/stage/{id}/delete', 'SettingsController@deleteStage', ['AuthMiddleware', 'CsrfMiddleware', 'AdminMiddleware', 'RateLimitMiddleware']);
Router::delete('/settings/stage/{id}', 'SettingsController@deleteStage', ['AuthMiddleware', 'CsrfMiddleware', 'AdminMiddleware']);

// ══════════════════════════════════════
// Legal (públicas)
// ══════════════════════════════════════
Router::get('/terms', 'LegalController@terms');
Router::get('/privacy', 'LegalController@privacy');

// ══════════════════════════════════════
// Account (LGPD)
// ══════════════════════════════════════
Router::get('/account/delete', 'AccountController@deleteForm', ['AuthMiddleware']);
Router::post('/account/delete', 'AccountController@delete', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::get('/account/export', 'AccountController@export', ['AuthMiddleware']);
Router::post('/account/export', 'AccountController@exportDownload', ['AuthMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);

// ══════════════════════════════════════
// Password Recovery
// ══════════════════════════════════════
Router::get('/forgot-password', 'AuthController@forgotPasswordForm', ['GuestMiddleware']);
Router::post('/forgot-password', 'AuthController@forgotPassword', ['GuestMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);
Router::get('/reset-password', 'AuthController@resetPasswordForm', ['GuestMiddleware']);
Router::post('/reset-password', 'AuthController@resetPassword', ['GuestMiddleware', 'CsrfMiddleware', 'RateLimitMiddleware']);