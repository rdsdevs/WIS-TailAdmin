@push('scripts')
<script>
function cargoFormData(init) {
    return {
        positionName:  init.positionName,
        editingName:   init.editingName,
        emails:        init.emails,
        functions:     init.functions,
        responsibilities: init.responsibilities,
        authorities:   init.authorities,

        editingFunc:                 null,
        editingResp:                 null,
        editingAuth:                 null,
        editingEmail:                null,
        confirmingFuncDelete:        null,
        confirmingRespDelete:        null,
        confirmingAuthDelete:        null,
        confirmingEmailDelete:       null,

        /* ── Funciones ────────────────────────────────────── */
        addFunction() {
            if (this.editingFunc !== null && (this.functions[this.editingFunc] || '').trim() !== '') {
                this.editingFunc = null;
            }
            this.functions.push('');
            this.$nextTick(() => { this.editingFunc = this.functions.length - 1; });
        },
        removeFunction(i) { this.functions.splice(i, 1); },
        confirmFunc(i) {
            this.confirmingFuncDelete = i;
            setTimeout(() => { if (this.confirmingFuncDelete === i) this.confirmingFuncDelete = null; }, 2500);
        },
        doRemoveFunction(i) { this.removeFunction(i); this.confirmingFuncDelete = null; },

        /* ── Responsabilidades ────────────────────────────── */
        addResponsibility() {
            if (this.editingResp !== null && (this.responsibilities[this.editingResp] || '').trim() !== '') {
                this.editingResp = null;
            }
            this.responsibilities.push('');
            this.$nextTick(() => { this.editingResp = this.responsibilities.length - 1; });
        },
        removeResponsibility(i) { this.responsibilities.splice(i, 1); },
        confirmResp(i) {
            this.confirmingRespDelete = i;
            setTimeout(() => { if (this.confirmingRespDelete === i) this.confirmingRespDelete = null; }, 2500);
        },
        doRemoveResponsibility(i) { this.removeResponsibility(i); this.confirmingRespDelete = null; },

        /* ── Autoridades ──────────────────────────────────── */
        addAuthority() {
            if (this.editingAuth !== null && (this.authorities[this.editingAuth] || '').trim() !== '') {
                this.editingAuth = null;
            }
            this.authorities.push('');
            this.$nextTick(() => { this.editingAuth = this.authorities.length - 1; });
        },
        removeAuthority(i) { this.authorities.splice(i, 1); },
        confirmAuth(i) {
            this.confirmingAuthDelete = i;
            setTimeout(() => { if (this.confirmingAuthDelete === i) this.confirmingAuthDelete = null; }, 2500);
        },
        doRemoveAuthority(i) { this.removeAuthority(i); this.confirmingAuthDelete = null; },

        /* ── Correos ──────────────────────────────────────── */
        addEmail() {
            if (this.editingEmail !== null
                && this.isValidEmail(this.emails[this.editingEmail] || '')
                && !this.isDuplicateEmail(this.emails[this.editingEmail], this.editingEmail)) {
                this.editingEmail = null;
            }
            this.emails.push('');
            this.$nextTick(() => { this.editingEmail = this.emails.length - 1; });
        },
        removeEmail(i) { this.emails.splice(i, 1); },
        confirmEmail(i) {
            this.confirmingEmailDelete = i;
            setTimeout(() => { if (this.confirmingEmailDelete === i) this.confirmingEmailDelete = null; }, 2500);
        },
        doRemoveEmail(i) { this.removeEmail(i); this.confirmingEmailDelete = null; },

        /* ── Utilidades de correo ─────────────────────────── */
        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },
        isDuplicateEmail(email, index) {
            if (!email) return false;
            return this.emails.some((e, i) => i !== index && e.toLowerCase() === email.toLowerCase());
        },
    };
}
</script>
@endpush
