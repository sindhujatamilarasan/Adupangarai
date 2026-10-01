import { PasswordForm } from '../components/PasswordForm'

/** Opened from the reset email (adupangarai://reset-password?token=…&email=…). */
export default function ResetPassword() {
  return <PasswordForm mode="reset" />
}
