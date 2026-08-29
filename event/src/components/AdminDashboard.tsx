import { useState } from "react"
import { EVENTS, formatDate, formatPrice } from "../data/events"

type Tab = "overview" | "events" | "users" | "payments" | "settings"

function StatCard({ label, value, delta, positive }: { label: string; value: string; delta: string; positive: boolean }) {
  return (
    <div className="p-5 rounded-xl bg-surface border border-rim">
      <p className="text-muted text-xs font-medium uppercase tracking-wide mb-2">{label}</p>
      <p className="font-display text-3xl font-bold text-white mb-1">{value}</p>
      <p className={`text-xs font-medium ${positive ? "text-success" : "text-warning"}`}>{delta}</p>
    </div>
  )
}

const USERS = [
  { name: "Jordan Lee", email: "jordan@example.com", role: "customer", joined: "Jan 2026", bookings: 3, spent: "$983" },
  { name: "Pulse Events Co.", email: "pulse@events.com", role: "organizer", joined: "Feb 2025", bookings: 0, spent: "—" },
  { name: "Ostgut Events", email: "info@ostgut.de", role: "organizer", joined: "Mar 2025", bookings: 0, spent: "—" },
  { name: "Live Nation", email: "admin@livenation.com", role: "organizer", joined: "Nov 2024", bookings: 0, spent: "—" },
  { name: "Sam Porter", email: "sam@email.com", role: "customer", joined: "Mar 2026", bookings: 7, spent: "$2,450" },
  { name: "Mei Zhang", email: "mei@email.com", role: "customer", joined: "Apr 2026", bookings: 2, spent: "$320" },
  { name: "David Okoye", email: "david@email.com", role: "admin", joined: "Jan 2024", bookings: 0, spent: "—" },
]

const PAYMENTS = [
  { id: "pay-001", user: "Jordan Lee", event: "Neon Frequencies", amount: "$698", status: "Completed", date: "Aug 10" },
  { id: "pay-002", user: "Sam Porter", event: "Arctic Monkeys", amount: "$280", status: "Completed", date: "Aug 12" },
  { id: "pay-003", user: "Mei Zhang", event: "TechSummit 2026", amount: "$299", status: "Completed", date: "Aug 14" },
  { id: "pay-004", user: "Jordan Lee", event: "Arctic Monkeys", amount: "$120", status: "Completed", date: "Aug 15" },
  { id: "pay-005", user: "Casey Kim", event: "Champions League", amount: "$890", status: "Pending", date: "Aug 20" },
  { id: "pay-006", user: "Robin West", event: "Afrobeats Night", amount: "$165", status: "Refunded", date: "Aug 22" },
]

const ROLE_COLORS: Record<string, string> = {
  customer: "bg-purple/15 text-purple-glow border-purple/25",
  organizer: "bg-pink/15 text-pink border-pink/25",
  admin: "bg-warning/15 text-warning border-warning/25",
}

const STATUS_COLORS: Record<string, string> = {
  Completed: "bg-success/15 text-success border-success/25",
  Pending: "bg-warning/15 text-warning border-warning/25",
  Refunded: "bg-muted/15 text-muted border-rim",
}

export default function AdminDashboard() {
  const [tab, setTab] = useState<Tab>("overview")

  const tabs: { id: Tab; label: string }[] = [
    { id: "overview", label: "Overview" },
    { id: "events", label: "All Events" },
    { id: "users", label: "Users" },
    { id: "payments", label: "Payments" },
    { id: "settings", label: "Settings" },
  ]

  return (
    <div className="min-h-screen bg-void pt-16">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 py-10">
        {/* Header */}
        <div className="mb-8">
          <p className="text-warning text-xs font-semibold uppercase tracking-widest mb-1">Administration</p>
          <h1 className="font-display text-4xl font-bold text-white">Platform Admin</h1>
          <p className="text-muted mt-1">Full visibility and control over the EventHub Pro platform</p>
        </div>

        {/* Tabs */}
        <div className="flex gap-1 p-1 rounded-xl bg-surface border border-rim mb-8 overflow-x-auto w-fit">
          {tabs.map((t) => (
            <button
              key={t.id}
              onClick={() => setTab(t.id)}
              className={`px-5 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap ${tab === t.id ? "bg-warning/20 text-warning border border-warning/30" : "text-muted hover:text-white"}`}
            >
              {t.label}
            </button>
          ))}
        </div>

        {/* Overview */}
        {tab === "overview" && (
          <div className="space-y-8">
            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
              <StatCard label="Total Events" value={EVENTS.length.toString()} delta="↑ 4 this month" positive />
              <StatCard label="Registered Users" value="24,812" delta="↑ 12% this month" positive />
              <StatCard label="Gross Revenue" value="$2.4M" delta="↑ 18% vs last month" positive />
              <StatCard label="Tickets Sold" value="47,290" delta="↑ 9% this week" positive />
            </div>

            {/* Platform health */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
              {[
                { label: "System Status", value: "All Systems Operational", color: "text-success", icon: "✓" },
                { label: "Active Organizers", value: "214 Verified", color: "text-purple-glow", icon: "🏢" },
                { label: "Pending Reviews", value: "8 Flagged Events", color: "text-warning", icon: "⚠" },
              ].map((item) => (
                <div key={item.label} className="p-5 rounded-xl bg-surface border border-rim flex items-center gap-4">
                  <span className="text-2xl">{item.icon}</span>
                  <div>
                    <p className="text-muted text-xs uppercase tracking-wide mb-0.5">{item.label}</p>
                    <p className={`font-semibold text-sm ${item.color}`}>{item.value}</p>
                  </div>
                </div>
              ))}
            </div>

            {/* Revenue chart */}
            <div className="p-6 rounded-xl bg-surface border border-rim">
              <h3 className="font-display text-lg font-semibold text-white mb-6">Monthly Revenue</h3>
              <div className="flex items-end gap-3 h-40">
                {[
                  { month: "Jan", val: 140 }, { month: "Feb", val: 180 }, { month: "Mar", val: 210 },
                  { month: "Apr", val: 165 }, { month: "May", val: 240 }, { month: "Jun", val: 290 },
                  { month: "Jul", val: 320 }, { month: "Aug", val: 380 },
                ].map((item) => {
                  const pct = (item.val / 380) * 100
                  return (
                    <div key={item.month} className="flex-1 flex flex-col items-center gap-2">
                      <span className="text-muted text-xs">${item.val}k</span>
                      <div className="w-full rounded-t-md" style={{ height: `${pct}%`, background: "linear-gradient(to top, #f59e0b66, #f59e0b)" }} />
                      <span className="text-muted text-xs">{item.month}</span>
                    </div>
                  )
                })}
              </div>
            </div>

            {/* Recent activity */}
            <div className="p-6 rounded-xl bg-surface border border-rim">
              <h3 className="font-display text-lg font-semibold text-white mb-4">Recent Activity</h3>
              <div className="space-y-3">
                {[
                  { action: "New event submitted for review", detail: "World Jazz Night by Ostgut Events", time: "2 min ago", type: "event" },
                  { action: "High-value booking completed", detail: "Champions League Cat.1 — $890", time: "14 min ago", type: "payment" },
                  { action: "New organizer registered", detail: "Aurora Concerts Ltd.", time: "1 hr ago", type: "user" },
                  { action: "Refund processed", detail: "Booking bk-8z1q — $165", time: "3 hr ago", type: "refund" },
                  { action: "Event reported by user", detail: "Midnight Techno: content policy", time: "5 hr ago", type: "flag" },
                ].map((item, i) => {
                  const colors: Record<string, string> = { event: "bg-purple/20 text-purple", payment: "bg-success/20 text-success", user: "bg-pink/20 text-pink", refund: "bg-muted/20 text-muted", flag: "bg-warning/20 text-warning" }
                  const icons: Record<string, string> = { event: "📅", payment: "💳", user: "👤", refund: "↩", flag: "🚩" }
                  return (
                    <div key={i} className="flex items-center gap-4 py-3 border-b border-rim last:border-0">
                      <span className={`w-8 h-8 rounded-lg flex items-center justify-center text-sm ${colors[item.type]}`}>{icons[item.type]}</span>
                      <div className="flex-1 min-w-0">
                        <p className="text-white text-sm font-medium">{item.action}</p>
                        <p className="text-muted text-xs">{item.detail}</p>
                      </div>
                      <span className="text-muted text-xs shrink-0">{item.time}</span>
                    </div>
                  )
                })}
              </div>
            </div>
          </div>
        )}

        {/* All Events */}
        {tab === "events" && (
          <div className="rounded-xl bg-surface border border-rim overflow-hidden">
            <div className="p-5 border-b border-rim flex items-center justify-between">
              <h3 className="font-display font-semibold text-white">All Platform Events ({EVENTS.length})</h3>
              <div className="flex gap-2">
                <select className="bg-ghost border border-rim rounded-lg px-3 py-1.5 text-sm text-muted focus:outline-none">
                  <option>All Status</option>
                  <option>Active</option>
                  <option>Pending</option>
                  <option>Flagged</option>
                </select>
              </div>
            </div>
            <div className="divide-y divide-rim">
              {EVENTS.map((event) => (
                <div key={event.id} className="flex items-center gap-4 px-5 py-4 hover:bg-ghost/50 transition-colors">
                  <div className="w-10 h-10 rounded-lg overflow-hidden bg-elevated shrink-0">
                    <img src={event.image} alt="" className="w-full h-full object-cover" />
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-white text-sm font-medium truncate">{event.title}</p>
                    <p className="text-muted text-xs">{event.organizer} · {formatDate(event.date)}</p>
                  </div>
                  <div className="text-right shrink-0 hidden sm:block">
                    <p className="text-white text-xs font-medium">{event.category}</p>
                    <p className="text-muted text-xs">{event.city}</p>
                  </div>
                  <span className="px-2.5 py-1 rounded-full text-xs border bg-success/15 text-success border-success/25 shrink-0">Active</span>
                  <button className="text-muted hover:text-white text-xs shrink-0 transition-colors">Manage</button>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Users */}
        {tab === "users" && (
          <div className="rounded-xl bg-surface border border-rim overflow-hidden">
            <div className="p-5 border-b border-rim flex items-center justify-between">
              <h3 className="font-display font-semibold text-white">Platform Users</h3>
              <span className="text-muted text-sm">24,812 registered</span>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-rim">
                    {["Name", "Email", "Role", "Joined", "Bookings", "Spent"].map((h) => (
                      <th key={h} className="text-left px-5 py-3 text-muted text-xs font-medium uppercase tracking-wide">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-rim">
                  {USERS.map((u, i) => (
                    <tr key={i} className="hover:bg-ghost/50 transition-colors">
                      <td className="px-5 py-4">
                        <div className="flex items-center gap-3">
                          <div className="w-7 h-7 rounded-full gradient-btn flex items-center justify-center text-white text-xs font-bold shrink-0">
                            {u.name[0]}
                          </div>
                          <span className="text-white text-sm font-medium">{u.name}</span>
                        </div>
                      </td>
                      <td className="px-5 py-4 text-muted text-sm">{u.email}</td>
                      <td className="px-5 py-4">
                        <span className={`px-2.5 py-1 rounded-full text-xs border ${ROLE_COLORS[u.role]}`}>{u.role}</span>
                      </td>
                      <td className="px-5 py-4 text-muted text-sm">{u.joined}</td>
                      <td className="px-5 py-4 text-white text-sm">{u.bookings}</td>
                      <td className="px-5 py-4 text-white text-sm font-medium">{u.spent}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {/* Payments */}
        {tab === "payments" && (
          <div className="space-y-6">
            <div className="grid grid-cols-3 gap-4">
              <StatCard label="Gross Today" value="$18,240" delta="↑ 22% vs yesterday" positive />
              <StatCard label="Refunds Pending" value="$2,340" delta="4 open requests" positive={false} />
              <StatCard label="Platform Fee Revenue" value="$47,800" delta="This month" positive />
            </div>
            <div className="rounded-xl bg-surface border border-rim overflow-hidden">
              <div className="p-5 border-b border-rim">
                <h3 className="font-display font-semibold text-white">Recent Transactions</h3>
              </div>
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="border-b border-rim">
                      {["ID", "Customer", "Event", "Amount", "Status", "Date"].map((h) => (
                        <th key={h} className="text-left px-5 py-3 text-muted text-xs font-medium uppercase tracking-wide">{h}</th>
                      ))}
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-rim">
                    {PAYMENTS.map((p) => (
                      <tr key={p.id} className="hover:bg-ghost/50 transition-colors">
                        <td className="px-5 py-4 text-muted text-xs font-mono">{p.id}</td>
                        <td className="px-5 py-4 text-white text-sm font-medium">{p.user}</td>
                        <td className="px-5 py-4 text-muted text-sm">{p.event}</td>
                        <td className="px-5 py-4 text-white text-sm font-semibold">{p.amount}</td>
                        <td className="px-5 py-4">
                          <span className={`px-2.5 py-1 rounded-full text-xs border ${STATUS_COLORS[p.status]}`}>{p.status}</span>
                        </td>
                        <td className="px-5 py-4 text-muted text-sm">{p.date}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        )}

        {/* Settings */}
        {tab === "settings" && (
          <div className="max-w-2xl space-y-6">
            {[
              { section: "Platform", items: [
                { label: "Platform Name", value: "EventHub Pro", type: "text" },
                { label: "Support Email", value: "support@eventhubpro.com", type: "text" },
                { label: "Service Fee (%)", value: "12", type: "text" },
              ]},
              { section: "Permissions", items: [
                { label: "Allow Public Event Listings", value: "true", type: "toggle" },
                { label: "Require Organizer Verification", value: "true", type: "toggle" },
                { label: "Enable Review System", value: "true", type: "toggle" },
              ]},
            ].map((group) => (
              <div key={group.section} className="p-6 rounded-xl bg-surface border border-rim space-y-4">
                <h3 className="font-display text-lg font-semibold text-white">{group.section}</h3>
                {group.items.map((item) => (
                  <div key={item.label} className="flex items-center justify-between gap-4">
                    <label className="text-white text-sm font-medium">{item.label}</label>
                    {item.type === "toggle" ? (
                      <div className={`w-11 h-6 rounded-full border ${item.value === "true" ? "gradient-btn border-purple/40" : "bg-ghost border-rim"} relative cursor-pointer transition-all`}>
                        <div className={`absolute top-0.5 w-5 h-5 rounded-full bg-white transition-all ${item.value === "true" ? "left-5" : "left-0.5"}`} />
                      </div>
                    ) : (
                      <input
                        type="text"
                        defaultValue={item.value}
                        className="w-48 bg-ghost border border-rim rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-purple/60 transition-colors"
                      />
                    )}
                  </div>
                ))}
              </div>
            ))}
            <button className="gradient-btn px-6 py-3 rounded-xl text-white font-semibold text-sm">Save Settings</button>
          </div>
        )}
      </div>
    </div>
  )
}
