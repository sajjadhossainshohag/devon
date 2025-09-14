<?php

namespace App\Livewire;

use App\Services\ValetService;
use Livewire\Component;

class Dashboard extends Component
{
    public $sites = [];
    public $status = [];
    public $phpVersion = '';
    public $showLinkModal = false;
    public $siteName = '';
    public $sitePath = '';
    public $loading = false;
    public $message = '';
    public $messageType = 'success'; // success, error, info

    protected $rules = [
        'siteName' => 'required|string|max:255',
        'sitePath' => 'required|string|max:500',
    ];

    public function mount(ValetService $valetService)
    {
        $this->loadData($valetService);
    }

    public function loadData(ValetService $valetService)
    {
        try {
            $this->sites = $valetService->getSites();
            $this->status = $valetService->getValetStatus();
            $this->phpVersion = $valetService->getPhpVersion();
        } catch (\Exception $e) {
            $this->showMessage('Error loading data: ' . $e->getMessage(), 'error');
        }
    }

    public function refreshData()
    {
        $this->loadData(app(ValetService::class));
        $this->showMessage('Data refreshed successfully', 'success');
    }

    public function showLinkSiteModal()
    {
        $this->showLinkModal = true;
        $this->resetForm();
    }

    public function hideLinkSiteModal()
    {
        $this->showLinkModal = false;
        $this->resetForm();
    }

    public function linkSite(ValetService $valetService)
    {
        $this->validate();
        $this->loading = true;

        try {
            $success = $valetService->linkSite($this->siteName, $this->sitePath);

            if ($success) {
                $this->showMessage("Site '{$this->siteName}' linked successfully!", 'success');
                $this->hideLinkSiteModal();
                $this->loadData($valetService);
            } else {
                $this->showMessage('Failed to link site. Please check the path and try again.', 'error');
            }
        } catch (\Exception $e) {
            $this->showMessage('Error linking site: ' . $e->getMessage(), 'error');
        } finally {
            $this->loading = false;
        }
    }

    public function unlinkSite($siteName, ValetService $valetService)
    {
        $this->loading = true;

        try {
            $success = $valetService->unlinkSite($siteName);

            if ($success) {
                $this->showMessage("Site '{$siteName}' unlinked successfully!", 'success');
                $this->loadData($valetService);
            } else {
                $this->showMessage('Failed to unlink site.', 'error');
            }
        } catch (\Exception $e) {
            $this->showMessage('Error unlinking site: ' . $e->getMessage(), 'error');
        } finally {
            $this->loading = false;
        }
    }

    public function secureSite($siteName, ValetService $valetService)
    {
        $this->loading = true;

        try {
            $success = $valetService->secureSite($siteName);

            if ($success) {
                $this->showMessage("Site '{$siteName}' is now secured with SSL!", 'success');
                $this->loadData($valetService);
            } else {
                $this->showMessage('Failed to secure site.', 'error');
            }
        } catch (\Exception $e) {
            $this->showMessage('Error securing site: ' . $e->getMessage(), 'error');
        } finally {
            $this->loading = false;
        }
    }

    public function restartValet(ValetService $valetService)
    {
        $this->loading = true;

        try {
            $success = $valetService->restartValet();

            if ($success) {
                $this->showMessage('Valet restarted successfully!', 'success');
                $this->loadData($valetService);
            } else {
                $this->showMessage('Failed to restart Valet.', 'error');
            }
        } catch (\Exception $e) {
            $this->showMessage('Error restarting Valet: ' . $e->getMessage(), 'error');
        } finally {
            $this->loading = false;
        }
    }

    private function resetForm()
    {
        $this->siteName = '';
        $this->sitePath = '';
        $this->resetValidation();
    }

    private function showMessage($message, $type = 'success')
    {
        $this->message = $message;
        $this->messageType = $type;

        // Auto-hide message after 5 seconds
        $this->dispatch('hide-message');
    }

    public function clearMessage()
    {
        $this->message = '';
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
