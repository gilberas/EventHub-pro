import { useState } from "react"
import { EVENTS, formatDate, formatPrice } from "../data/events"

const ORG_EVENTS = EVENTS.filter((e) => e.organizer === "Pulse Events Co." || e.id === "evt-001" || e.id === "evt-010").slice(0, 3)

type Tab = "overview" | "events" | "attendees" | "analytics"

function StatCard({ label, value, sub, color }: { label: string; value: string; sub: string; color: string }) {
  return (
    <div className="p-5 rounded-xl bg-surface border border-rim">
      <p className="text-muted text-xs font-medium uppercase tracking-wide mb-2">{label}</p>
      <p className={`font-display text-3xl font-bold ${color} mb-1`}>{value}</p>
      <p className="text-muted text-xs">{sub}</p>
    </div>
  )
}

export default function OrganizerDashboard() {
  const [tab, setTab] = useState<Tab>("overview")
  const [showCreate, setShowCreate] = useState(false)
  const [newEvent, setNewEvent] = useState({ title: "", category: "Concerts", date: "", venue: "", capacity: "", price: "" })

  const tabs: { id: Tab; label: string }[] = [
    { id: "overview", label: "Overview" },
    { id: "events", label: "My Events" },
    { id: "attendees", label: "Attendees" },
    { id: "analytics", label: "Analytics" },
  ]

  const ATTENDEES = [
    { name: "Alex Morrison", email: "alex@email.com", event: "Neon Frequencies", tier: "VIP", qty: 2, date: "Aug 10" },
    { name: "Sara Kim", email: "sara@email.com", event: "Neon Frequencies", tier: "General", qty: 4, date: "Aug 11" },
    { name: "Tom Richards", email: "tom@email.com", event: "Neon Frequencies", tier: "Platinum", qty: 1, date: "Aug 12" },
    { name: "Mia Patel", email: "mia@email.com", event: "Neon Frequencies", tier: "General", qty: 2, date: "Aug 13" },
    { name: "James Wu", email: "james@email.com", event: "Midnight Techno", tier: "Presale", qty: 2, date: "Aug 14" },
    { name: "Olivia Sanz", email: "oli@email.com", event: "Midnight Techno", tier: "Door", qty: 1, date: "Aug 15" },
  ]

  return (
    <div className="min-h-screen bg-void pt-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-10">
        {/* Header */}
        <div className="flex items-start justify-between mb-8">
          <div>
            <p className="text-pink text-xs font-semibold uppercase tracking-widest mb-1">Organizer Portal</p>
            <h1 className="font-display text-4xl font-bold text-white">Pulse Events Co.</h1>
            <p className="text-muted mt-1">Manage your events, tickets, and attendees</p>
          </div>
          <button
            onClick={() => setShowCreate(true)}
            className="gradient-btn px-5 py-2.5 rounded-xl text-white font-semibold text-sm flex items-center gap-2"
          >
            <svg viewBox="0 0 24 24" className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2.5">
              <path d="M12 5v14M5 12h14"/>
            </svg>
            Create Event
          </button>
        </div>

        {/* Tabs */}
        <div className="flex gap-1 p-1 rounded-xl bg-surface border border-rim mb-8 w-fit">
          {tabs.map((t) => (
            <button
              key={t.id}
              onClick={() => setTab(t.id)}
              className={`px-5 py-2 rounded-lg text-sm font-medium transition-all ${tab === t.id ? "bg-purple/20 text-purple-glow border border-purple/30" : "text-muted hover:text-white"}`}
            >
              {t.label}
            </button>
          ))}
        </div>

        {/* Overview */}
        {tab === "overview" && (
          <div className="space-y-8">
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
              <StatCard label="Total Events" value="3" sub="2 upcoming" color="text-white" />
              <StatCard label="Tickets Sold" value="1,240" sub="+12% this month" color="gradient-text" />
              <StatCard label="Revenue" value="$184,600" sub="After platform fees" color="text-success" />
              <StatCard label="Avg Rating" value="4.85" sub="Across all events" color="text-warning" />
            </div>

            {/* Recent sales chart (visual-only bars) */}
            <div className="p-6 rounded-xl bg-surface border border-rim">
              <h3 className="font-display text-lg font-semibold text-white mb-6">Ticket Sales — Last 7 Days</h3>
              <div className="flex items-end gap-3 h-32">
                {[42, 78, 55, 91, 63, 110, 88].map((val, i) => {
                  const days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"]
                  const pct = (val / 110) * 100
                  return (
                    <div key={i} className="flex-1 flex flex-col items-center gap-2">
                      <span className="text-muted text-xs">{val}</span>
                      <div className="w-full rounded-t-md transition-all" style={{ height: `${pct}%`, background: "linear-gradient(to top, #a855f7, #ec4899)" }} />
                      <span className="text-muted text-xs">{days[i]}</span>
                    </div>
                  )
                })}
              </div>
            </div>

            {/* Events quick view */}
            <div className="p-6 rounded-xl bg-surface border border-rim">
              <h3 className="font-display text-lg font-semibold text-white mb-4">Your Events</h3>
              <div className="space-y-3">
                {ORG_EVENTS.map((event) => {
                  const soldPct = Math.round((Math.random() * 0.5 + 0.4) * 100)
                  return (
                    <div key={event.id} className="flex items-center gap-4 p-4 rounded-xl bg-ghost border border-rim">
                      <div className="w-12 h-12 rounded-lg overflow-hidden bg-elevated shrink-0">
                        <img src={event.image} alt={event.title} className="w-full h-full object-cover" />
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-white font-medium text-sm truncate">{event.title}</p>
                        <p className="text-muted text-xs">{formatDate(event.date)} · {event.venue}</p>
                      </div>
                      <div className="text-right shrink-0">
                        <p className="text-white text-sm font-semibold">{soldPct}% sold</p>
                        <div className="w-24 h-1.5 bg-ghost rounded-full mt-1">
                          <div className="h-full rounded-full gradient-btn" style={{ width: `${soldPct}%` }} />
                        </div>
                      </div>
                    </div>
                  )
                })}
              </div>
            </div>
          </div>
        )}

        {/* Events */}
        {tab === "events" && (
          <div className="space-y-4">
            {ORG_EVENTS.map((event) => (
              <div key={event.id} className="p-5 rounded-xl bg-surface border border-rim flex gap-5 items-start">
                <div className="w-24 h-16 rounded-lg overflow-hidden bg-ghost shrink-0">
                  <img src={event.image} alt={event.title} className="w-full h-full object-cover" />
                </div>
                <div className="flex-1 min-w-0">
                  <h3 className="font-display font-semibold text-white">{event.title}</h3>
                  <p className="text-muted text-sm mt-0.5">{formatDate(event.date)} · {event.venue}, {event.city}</p>
                  <div className="flex flex-wrap gap-4 mt-3 text-xs">
                    <span className="text-success">✓ Active</span>
                    <span className="text-muted">Capacity: {event.capacity.toLocaleString()}</span>
                    <span className="text-muted">Min price: {formatPrice(Math.min(...event.ticketTiers.map(t => t.price)))}</span>
                    {event.hasReservedSeating && <span className="text-purple-glow">Reserved seating</span>}
                  </div>
                </div>
                <div className="flex gap-2 shrink-0">
                  <button className="px-3 py-1.5 rounded-lg text-xs font-medium bg-ghost border border-rim text-muted hover:text-white transition-colors">Edit</button>
                  <button className="px-3 py-1.5 rounded-lg text-xs font-medium bg-ghost border border-rim text-muted hover:text-white transition-colors">Analytics</button>
                </div>
              </div>
            ))}
          </div>
        )}

        {/* Attendees */}
        {tab === "attendees" && (
          <div className="rounded-xl bg-surface border border-rim overflow-hidden">
            <div className="p-5 border-b border-rim flex items-center justify-between">
              <h3 className="font-display font-semibold text-white">All Attendees</h3>
              <button className="px-4 py-2 rounded-lg text-xs font-medium bg-ghost border border-rim text-muted hover:text-white transition-colors">Export CSV</button>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-rim">
                    {["Name", "Email", "Event", "Tier", "Qty", "Purchased"].map((h) => (
                      <th key={h} className="text-left px-5 py-3 text-muted text-xs font-medium uppercase tracking-wide">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-rim">
                  {ATTENDEES.map((a, i) => (
                    <tr key={i} className="hover:bg-ghost/50 transition-colors">
                      <td className="px-5 py-4 text-white text-sm font-medium">{a.name}</td>
                      <td className="px-5 py-4 text-muted text-sm">{a.email}</td>
                      <td className="px-5 py-4 text-white text-sm">{a.event}</td>
                      <td className="px-5 py-4">
                        <span className="px-2.5 py-1 rounded-full text-xs bg-purple/15 text-purple-glow border border-purple/20">{a.tier}</span>
                      </td>
                      <td className="px-5 py-4 text-white text-sm">{a.qty}</td>
                      <td className="px-5 py-4 text-muted text-sm">{a.date}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Analytics */}
        {tab === "analytics" && (
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Revenue by tier */}
            <div className="p-6 rounded-xl bg-surface border border-rim">
              <h3 className="font-display text-lg font-semibold text-white mb-5">Revenue by Ticket Tier</h3>
              <div className="space-y-4">
                {[
                  { tier: "General Admission", pct: 48, revenue: "$88,512", color: "#a855f7" },
                  { tier: "VIP", pct: 35, revenue: "$64,470", color: "#ec4899" },
                  { tier: "Platinum", pct: 17, revenue: "$31,362", color: "#f59e0b" },
                ].map((item) => (
                  <div key={item.tier}>
                    <div className="flex justify-between text-sm mb-1.5">
                      <span className="text-white font-medium">{item.tier}</span>
                      <span className="text-muted">{item.revenue}</span>
                    </div>
                    <div className="h-2 bg-ghost rounded-full">
                      <div className="h-full rounded-full transition-all" style={{ width: `${item.pct}%`, background: item.color }} />
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Attendance by city */}
            <div className="p-6 rounded-xl bg-surface border border-rim">
              <h3 className="font-display text-lg font-semibold text-white mb-5">Attendees by City</h3>
              <div className="space-y-3">
                {[
                  { city: "Austin, TX", count: 8420, pct: 30 },
                  { city: "New York, NY", count: 6120, pct: 22 },
                  { city: "Los Angeles, CA", count: 4890, pct: 17 },
                  { city: "Chicago, IL", count: 3650, pct: 13 },
                  { city: "Other", count: 5040, pct: 18 },
                ].map((item) => (
                  <div key={item.city} className="flex items-center gap-3">
                    <span className="text-muted text-sm w-36 shrink-0">{item.city}</span>
                    <div className="flex-1 h-1.5 bg-ghost rounded-full">
                      <div className="h-full rounded-full bg-purple/70" style={{ width: `${item.pct}%` }} />
                    </div>
                    <span className="text-white text-xs font-medium w-16 text-right">{item.count.toLocaleString()}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Create Event Modal */}
      {showCreate && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4" style={{ background: "rgba(7,7,15,0.8)", backdropFilter: "blur(8px)" }}>
          <div className="w-full max-w-lg rounded-2xl bg-surface border border-rim p-6 space-y-5">
            <div className="flex items-center justify-between">
              <h2 className="font-display text-xl font-semibold text-white">Create New Event</h2>
              <button onClick={() => setShowCreate(false)} className="text-muted hover:text-white transition-colors">
                <svg viewBox="0 0 24 24" className="w-5 h-5" fill="none" stroke="currentColor" strokeWidth="2">
                  <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
              </button>
            </div>
            {[
              { key: "title", label: "Event Title", placeholder: "e.g. Summer Beats Festival 2026" },
              { key: "date", label: "Date", placeholder: "YYYY-MM-DD" },
              { key: "venue", label: "Venue", placeholder: "Venue name and city" },
              { key: "capacity", label: "Capacity", placeholder: "e.g. 5000" },
              { key: "price", label: "Starting Price ($)", placeholder: "e.g. 49" },
            ].map((field) => (
              <div key={field.key}>
                <label className="text-muted text-xs font-medium uppercase tracking-wide block mb-1.5">{field.label}</label>
                <input
                  type="text"
                  placeholder={field.placeholder}
                  value={(newEvent as any)[field.key]}
                  onChange={(e) => setNewEvent((f) => ({ ...f, [field.key]: e.target.value }))}
                  className="w-full bg-ghost border border-rim rounded-xl px-4 py-3 text-sm text-white placeholder:text-muted focus:outline-none focus:border-purple/60 transition-colors"
                />
              </div>
            ))}
            <div className="flex gap-3 pt-2">
              <button onClick={() => setShowCreate(false)} className="flex-1 py-3 rounded-xl text-muted border border-rim bg-ghost hover:text-white transition-colors text-sm font-medium">Cancel</button>
              <button onClick={() => setShowCreate(false)} className="flex-1 py-3 rounded-xl gradient-btn text-white font-semibold text-sm">Create Event</button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
