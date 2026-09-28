import { EmptyState } from '../components/ui'

export default function ComingSoon({ title, emoji }: { title: string; emoji: string }) {
  return (
    <div>
      <h1 className="text-2xl font-extrabold">{title}</h1>
      <EmptyState emoji={emoji} title="Coming soon">This section is being cooked up.</EmptyState>
    </div>
  )
}
