# Changelog

All notable changes to this project will be documented in this file.

## [2026-02-05]

### Added
- **Realization Tracking**: Implemented comprehensive tracking system for Realizations.
  - Created `realization_tracks` table mirroring `financial_record_tracks`.
  - Added `RealizationObserver` to auto-log Create, Update, Delete events.
  - Added `RealizationHistory` Livewire component for viewing change logs.
  - Added "History" button in Realization table.
- **Realization Access Control (Locking System)**:
  - Implemented `status_realisasi` locking mechanism.
  - When `status_realisasi = 1` (Final), users with 'User' role cannot edit the record.
  - Added visual cues: Edit button becomes gray/disabled with tooltip "Data dikunci (Final)".
  - Enforced backend protection via `RealizationPolicy`.
  - Admins and Editors retain edit access to locked records.

### Changed
- **UI Improvements**:
  - Enhanced Delete button in history modals (circular design, trash icon, red color scheme).
  - Improved Realization table actions visibility based on user roles and record status.
