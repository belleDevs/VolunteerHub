<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\OrgController;
use App\Http\Controllers\VolunteerController;

// Authentication
Route::get('/', [AuthController::class, 'index'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showVolunteerRegister'])->name('register');
Route::get('/register/volunteer', [AuthController::class, 'showVolunteerRegister'])->name('register.volunteer');
Route::post('/register/volunteer', [AuthController::class, 'registerVolunteer']);
Route::get('/register/organization', [AuthController::class, 'showOrgRegister'])->name('register.org');
Route::post('/register/organization', [AuthController::class, 'registerOrg']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    
    // Profile Management
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile.show');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    
    // Notifications
    Route::post('/notifications/{id}/read', [AuthController::class, 'markNotificationRead'])->name('notifications.read');
    
    // Administrator Group
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::post('/orgs/{id}/approve', [AdminController::class, 'approveOrg'])->name('admin.orgs.approve');
        Route::post('/orgs/{id}/reject', [AdminController::class, 'rejectOrg'])->name('admin.orgs.reject');
        Route::post('/orgs/{id}/documents', [AdminController::class, 'requestOrgDocuments'])->name('admin.orgs.documents');
        Route::post('/chatbot/rules', [AdminController::class, 'storeChatbotRule'])->name('admin.chatbot.rules.store');
        Route::delete('/chatbot/rules/{id}', [AdminController::class, 'deleteChatbotRule'])->name('admin.chatbot.rules.destroy');
        Route::post('/chatbot/conversations/{chatbotResponse}/reply', [AdminController::class, 'replyToChatbotConversation'])->name('admin.chatbot.conversations.reply');
        Route::post('/broadcast', [AdminController::class, 'broadcast'])->name('admin.broadcast');
        Route::post('/events/{event}/approve', [AdminController::class, 'approveEvent'])->name('admin.events.approve');
        Route::post('/tasks/{task}/notify', [AdminController::class, 'notifyOverdueTask'])->name('admin.tasks.notify');
        Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
        Route::get('/reports/export/{format}', [AdminController::class, 'exportReport'])->name('admin.reports.export');
        Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('admin.audit');
        Route::get('/audit-logs/export/{format}', [AdminController::class, 'exportAudit'])->name('admin.audit.export');
        Route::get('/audit-logs/{auditLog}', [AdminController::class, 'auditLog'])->name('admin.audit.show');
    });

    // Partner Organization Group
    Route::prefix('org')->middleware('role:organization')->group(function () {
        Route::get('/dashboard', [OrgController::class, 'dashboard'])->name('org.dashboard');
        Route::post('/notifications', [OrgController::class, 'sendNotification'])->name('org.notifications.send');
        Route::post('/notifications/read-all', [OrgController::class, 'readAllOrganizationNotices'])->name('org.notifications.read-all');
        Route::get('/notifications/{notification}/open', [OrgController::class, 'openOrganizationNotice'])->name('org.notifications.open');
        Route::post('/notifications/{notification}/read', [OrgController::class, 'readOrganizationNotice'])->name('org.notifications.read');
        Route::post('/notifications/{notification}/dismiss', [OrgController::class, 'dismissOrganizationNotice'])->name('org.notifications.dismiss');
        Route::get('/mail', fn () => redirect()->route('org.dashboard', ['section' => 'notifications']))->name('org.mail');
        Route::post('/mail', [OrgController::class, 'saveMailSettings'])->name('org.mail.save');
        Route::post('/messages/presence', [OrgController::class, 'messagePresence'])->name('org.messages.presence');
        Route::post('/messages/{volunteer}/reply', [OrgController::class, 'replyMessage'])->name('org.messages.reply');
        Route::post('/messages/{volunteer}/takeover', [OrgController::class, 'takeOverMessage'])->name('org.messages.takeover');
        Route::post('/events', [OrgController::class, 'storeEvent'])->name('org.events.store');
        Route::patch('/events/{event}', [OrgController::class, 'updateEvent'])->name('org.events.update');
        Route::delete('/events/{event}', [OrgController::class, 'destroyEvent'])->name('org.events.destroy');
        Route::post('/tasks', [OrgController::class, 'storeTask'])->name('org.tasks.store');
        Route::patch('/tasks/{task}', [OrgController::class, 'updateTask'])->name('org.tasks.update');
        Route::delete('/tasks/{task}', [OrgController::class, 'destroyTask'])->name('org.tasks.destroy');
        Route::post('/assignments', [OrgController::class, 'assignVolunteer'])->name('org.assignments.store');
        Route::post('/tasks/{task}/outreach', [OrgController::class, 'sendTaskOutreach'])->name('org.tasks.outreach');
        Route::post('/applications/{application}/approve', [OrgController::class, 'approveApplication'])->name('org.applications.approve');
        Route::post('/applications/{application}/reject', [OrgController::class, 'rejectApplication'])->name('org.applications.reject');
        Route::post('/settings/profile', [OrgController::class, 'updateOrganizationProfile'])->name('org.settings.profile');
        Route::post('/settings/security', [OrgController::class, 'updateOrganizationSecurity'])->name('org.settings.security');
        Route::post('/settings/notifications', [OrgController::class, 'updateOrganizationNotifications'])->name('org.settings.notifications');
        Route::post('/settings/preferences', [OrgController::class, 'updateOrganizationPreferences'])->name('org.settings.preferences');
        Route::delete('/settings/sessions/{sessionId}', [OrgController::class, 'revokeOrganizationSession'])->name('org.settings.sessions.destroy');
        Route::post('/officers/albums', [OrgController::class, 'storeOfficerAlbum'])->name('org.officers.albums.store');
        Route::post('/officers', [OrgController::class, 'storeOfficer'])->name('org.officers.store');
        Route::delete('/officers/{officer}', [OrgController::class, 'destroyOfficer'])->name('org.officers.destroy');
        Route::post('/gallery', [OrgController::class, 'storeEventPhotos'])->name('org.gallery.store');
        Route::delete('/gallery/{photo}', [OrgController::class, 'destroyEventPhoto'])->name('org.gallery.destroy');
        Route::post('/documents', [OrgController::class, 'uploadDocument'])->name('org.documents.store');
        Route::get('/documents/{document}/download', [OrgController::class, 'downloadDocument'])->name('org.documents.download');
        Route::get('/assignments/{assignment}/proof', [OrgController::class, 'downloadCompletionProof'])->name('org.assignments.proof');
        Route::post('/assignments/{assignment}/approve', [OrgController::class, 'approveCompletion'])->name('org.assignments.approve');
        Route::post('/assignments/{assignment}/reject', [OrgController::class, 'rejectCompletion'])->name('org.assignments.reject');
        Route::post('/certificates', [OrgController::class, 'issueCertificate'])->name('org.certificates.store');
        Route::get('/certificates/{certificate}', [OrgController::class, 'downloadCertificate'])->name('org.certificates.download');
        Route::get('/reports', [OrgController::class, 'reports'])->name('org.reports');
        Route::get('/reports/export/{format}', [OrgController::class, 'exportReport'])->name('org.reports.export');
    });

    // Volunteer Group
    Route::prefix('volunteer')->middleware('role:volunteer')->group(function () {
        Route::get('/dashboard', [VolunteerController::class, 'dashboard'])->name('volunteer.dashboard');
        Route::post('/skills', [VolunteerController::class, 'addSkill'])->name('volunteer.skills.store');
        Route::delete('/skills/{id}', [VolunteerController::class, 'removeSkill'])->name('volunteer.skills.destroy');
        Route::post('/availability', [VolunteerController::class, 'toggleAvailability'])->name('volunteer.availability.toggle');
        Route::post('/tasks/{task}/apply', [VolunteerController::class, 'applyForTask'])->name('volunteer.tasks.apply');
        Route::post('/tasks/{id}/complete', [VolunteerController::class, 'completeTask'])->name('volunteer.tasks.complete');
        Route::post('/assignments/{id}/respond', [VolunteerController::class, 'respondToAssignment'])->name('volunteer.assignments.respond');
        Route::post('/notifications/read-all', [VolunteerController::class, 'readAllNotifications'])->name('volunteer.notifications.read-all');
        Route::post('/messages', [VolunteerController::class, 'sendMessage'])->name('volunteer.messages.store');
        Route::post('/settings', [VolunteerController::class, 'updateSettings'])->name('volunteer.settings.update');
        Route::get('/certificates/{id}/download', [VolunteerController::class, 'downloadCertificate'])->name('volunteer.certificates.download');
        Route::get('/certificates/{id}/pdf', [VolunteerController::class, 'pdfCertificate'])->name('volunteer.certificates.pdf');
        Route::post('/chatbot/ask', [VolunteerController::class, 'askChatbot'])->name('volunteer.chatbot.ask');
        Route::get('/chatbot/history', [VolunteerController::class, 'chatbotHistory'])->name('volunteer.chatbot.history');
        Route::get('/reports', [VolunteerController::class, 'reports'])->name('volunteer.reports');
        Route::get('/reports/export/{format}', [VolunteerController::class, 'exportReport'])->name('volunteer.reports.export');
    });
});
