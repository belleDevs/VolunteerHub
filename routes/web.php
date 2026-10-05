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
        Route::post('/chatbot/rules', [AdminController::class, 'storeChatbotRule'])->name('admin.chatbot.rules.store');
        Route::delete('/chatbot/rules/{id}', [AdminController::class, 'deleteChatbotRule'])->name('admin.chatbot.rules.destroy');
        Route::post('/chatbot/conversations/{chatbotResponse}/reply', [AdminController::class, 'replyToChatbotConversation'])->name('admin.chatbot.conversations.reply');
        Route::post('/broadcast', [AdminController::class, 'broadcast'])->name('admin.broadcast');
    });

    // Partner Organization Group
    Route::prefix('org')->middleware('role:organization')->group(function () {
        Route::get('/dashboard', [OrgController::class, 'dashboard'])->name('org.dashboard');
        Route::post('/events', [OrgController::class, 'storeEvent'])->name('org.events.store');
        Route::patch('/events/{event}', [OrgController::class, 'updateEvent'])->name('org.events.update');
        Route::delete('/events/{event}', [OrgController::class, 'destroyEvent'])->name('org.events.destroy');
        Route::patch('/tasks/{task}', [OrgController::class, 'updateTask'])->name('org.tasks.update');
        Route::delete('/tasks/{task}', [OrgController::class, 'destroyTask'])->name('org.tasks.destroy');
        Route::post('/assignments', [OrgController::class, 'assignVolunteer'])->name('org.assignments.store');
        Route::post('/tasks/{task}/outreach', [OrgController::class, 'sendTaskOutreach'])->name('org.tasks.outreach');
        Route::post('/applications/{application}/approve', [OrgController::class, 'approveApplication'])->name('org.applications.approve');
        Route::post('/applications/{application}/reject', [OrgController::class, 'rejectApplication'])->name('org.applications.reject');
        Route::post('/documents', [OrgController::class, 'uploadDocument'])->name('org.documents.store');
        Route::get('/documents/{document}/download', [OrgController::class, 'downloadDocument'])->name('org.documents.download');
        Route::get('/assignments/{assignment}/proof', [OrgController::class, 'downloadCompletionProof'])->name('org.assignments.proof');
        Route::post('/assignments/{assignment}/approve', [OrgController::class, 'approveCompletion'])->name('org.assignments.approve');
        Route::post('/assignments/{assignment}/reject', [OrgController::class, 'rejectCompletion'])->name('org.assignments.reject');
    });

    // Volunteer Group
    Route::prefix('volunteer')->middleware('role:volunteer')->group(function () {
        Route::get('/dashboard', [VolunteerController::class, 'dashboard'])->name('volunteer.dashboard');
        Route::post('/skills', [VolunteerController::class, 'addSkill'])->name('volunteer.skills.store');
        Route::delete('/skills/{id}', [VolunteerController::class, 'removeSkill'])->name('volunteer.skills.destroy');
        Route::post('/availability', [VolunteerController::class, 'toggleAvailability'])->name('volunteer.availability.toggle');
        Route::post('/tasks/{task}/apply', [VolunteerController::class, 'applyForTask'])->name('volunteer.tasks.apply');
        Route::post('/tasks/{id}/complete', [VolunteerController::class, 'completeTask'])->name('volunteer.tasks.complete');
        Route::get('/certificates/{id}/download', [VolunteerController::class, 'downloadCertificate'])->name('volunteer.certificates.download');
        Route::post('/chatbot/ask', [VolunteerController::class, 'askChatbot'])->name('volunteer.chatbot.ask');
        Route::get('/chatbot/history', [VolunteerController::class, 'chatbotHistory'])->name('volunteer.chatbot.history');
    });
});
