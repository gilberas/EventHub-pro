import { useState } from "react"

type UserRole = "customer" | "organizer" | "admin"
type View = "home" | "event" | "checkout" | "confirmation" | "my-tickets" | "organizer" | "admin"

type Props = {
  currentView: View
  userRole: UserRole
  onNavigate: (view: View) => void
  onRoleChange: (role: UserRole) => void
}

const ROLE_LABELS: Record<UserRole, string> = {
  customer: "Jordan Lee",
  organizer: "Pulse Events Co.",
  admin: "Platform Admin",
}

const ROLE_COLORS: Record<UserRole, string> = {
  customer: "bg-purple/20 text-purple-glow",
  organizer: "bg-pink/20 text-pink",
  admin: "bg-warning/20 text-warning",
}

export default function Navbar({ currentView, userRole, onNavigate, onRoleChange }: Props) {
  const [menuOpen, setMenuOpen] = useState(false)
  const [roleOpen, setRoleOpen] = useState(false)

  return (
    <nav className="fixed top-0 inset-x-0 z-50 border-b border-rim" style={{ background: "rgba(7,7,15,0.85)", backdropFilter: "blur(16px)" }}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        {/* Logo */}
        <button
          onClick={() => onNavigate("home")}
          className="flex items-center gap-2.5 shrink-0"
        >
          <div className="w-8 h-8 rounded-lg gradient-btn flex items-center justify-center">
            <svg viewBox="0 0 24 24" fill="white" className="w-4 h-4">
              <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" fill="none"/>
            </svg>
          </div>
          <span className="font-display text-lg font-semibold text-white tracking-tight">
            EventHub<span className="gradient-text">Pro</span>
          </span>
        </button>

        {/* Desktop nav */}
        <div className="hidden md:flex items-center gap-1">
          <button
            onClick={() => onNavigate("home")}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${currentView === "home" ? "text-white bg-white/8" : "text-muted hover:text-white"}`}
          >
            Discover
          </button>
          {(userRole === "customer" || userRole === "organizer" || userRole === "admin") && (
            <button
              onClick={() => onNavigate("my-tickets")}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${currentView === "my-tickets" ? "text-white bg-white/8" : "text-muted hover:text-white"}`}
            >
              My Tickets
            </button>
          )}
          {userRole === "organizer" && (
            <button
              onClick={() => onNavigate("organizer")}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${currentView === "organizer" ? "text-white bg-white/8" : "text-muted hover:text-white"}`}
            >
              Manage Events
            </button>
          )}
          {userRole === "admin" && (
            <button
              onClick={() => onNavigate("admin")}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${currentView === "admin" ? "text-white bg-white/8" : "text-muted hover:text-white"}`}
            >
              Admin Panel
            </button>
          )}
        </div>

        {/* Right side */}
        <div className="flex items-center gap-3">
          {/* Role switcher */}
          <div className="relative">
            <button
              onClick={() => setRoleOpen(!roleOpen)}
              className={`flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium border border-rim transition-colors hover:border-white/20 ${ROLE_COLORS[userRole]}`}
            >
              <span className="w-1.5 h-1.5 rounded-full bg-current" />
              {ROLE_LABELS[userRole]}
              <svg viewBox="0 0 24 24" className="w-3 h-3 opacity-60" fill="none" stroke="currentColor" strokeWidth="2">
                <path d="M6 9l6 6 6-6"/>
              </svg>
            </button>
            {roleOpen && (
              <div className="absolute right-0 top-10 w-48 rounded-xl border border-rim overflow-hidden shadow-2xl" style={{ background: "#1a1a2c" }}>
                {(["customer", "organizer", "admin"] as UserRole[]).map((role) => (
                  <button
                    key={role}
                    onClick={() => {
                      onRoleChange(role)
                      setRoleOpen(false)
                      if (role === "organizer") onNavigate("organizer")
                      else if (role === "admin") onNavigate("admin")
                      else onNavigate("home")
                    }}
                    className={`w-full px-4 py-3 text-left text-sm flex items-center gap-3 transition-colors hover:bg-white/5 ${userRole === role ? "text-white font-medium" : "text-muted"}`}
                  >
                    <span className={`w-2 h-2 rounded-full ${role === "customer" ? "bg-purple" : role === "organizer" ? "bg-pink" : "bg-warning"}`} />
                    <div>
                      <div className="font-medium">{ROLE_LABELS[role]}</div>
                      <div className="text-xs opacity-60 capitalize">{role}</div>
                    </div>
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Mobile menu toggle */}
          <button
            className="md:hidden p-2 rounded-lg text-muted hover:text-white"
            onClick={() => setMenuOpen(!menuOpen)}
          >
            <svg viewBox="0 0 24 24" className="w-5 h-5" fill="none" stroke="currentColor" strokeWidth="2">
              {menuOpen ? <path d="M18 6L6 18M6 6l12 12"/> : <path d="M3 12h18M3 6h18M3 18h18"/>}
            </svg>
          </button>
        </div>
      </div>

      {/* Mobile menu */}
      {menuOpen && (
        <div className="md:hidden border-t border-rim px-4 py-3 flex flex-col gap-1" style={{ background: "rgba(7,7,15,0.95)" }}>
          <button onClick={() => { onNavigate("home"); setMenuOpen(false) }} className="px-4 py-2.5 rounded-lg text-sm text-left text-muted hover:text-white">Discover</button>
          <button onClick={() => { onNavigate("my-tickets"); setMenuOpen(false) }} className="px-4 py-2.5 rounded-lg text-sm text-left text-muted hover:text-white">My Tickets</button>
          {userRole === "organizer" && <button onClick={() => { onNavigate("organizer"); setMenuOpen(false) }} className="px-4 py-2.5 rounded-lg text-sm text-left text-muted hover:text-white">Manage Events</button>}
          {userRole === "admin" && <button onClick={() => { onNavigate("admin"); setMenuOpen(false) }} className="px-4 py-2.5 rounded-lg text-sm text-left text-muted hover:text-white">Admin Panel</button>}
        </div>
      )}
    </nav>
  )
}
