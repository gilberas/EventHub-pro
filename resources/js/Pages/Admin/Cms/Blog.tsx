import DashboardLayout from '@/Layouts/DashboardLayout';
import { PageProps } from '@/types';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface PostItem {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    author_name: string | null;
    is_published: boolean;
    published_at: string | null;
    created_at: string;
}

interface Props extends PageProps {
    posts: { data: PostItem[] };
}

export default function AdminBlog({ posts }: Props) {
    const [editing, setEditing] = useState<Partial<PostItem> | null>(null);

    const save = () => {
        if (!editing) return;
        if (editing.id) {
            router.put(route('admin.cms.blog.update', editing.id), editing);
        } else {
            router.post(route('admin.cms.blog'), editing);
        }
        setEditing(null);
    };

    return (
        <DashboardLayout>
            <div className="mx-auto max-w-5xl px-4 py-8">
                <div className="mb-6 flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Blog Posts</h1>
                    <button
                        onClick={() =>
                            setEditing({
                                title: '',
                                content: '',
                                is_published: false,
                            })
                        }
                        className="rounded bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
                    >
                        New Post
                    </button>
                </div>

                {editing && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                        <div className="mx-4 w-full max-w-lg rounded-lg bg-white p-6">
                            <h2 className="mb-4 text-lg font-bold">
                                {editing.id ? 'Edit Post' : 'New Post'}
                            </h2>
                            <div className="space-y-3">
                                <input
                                    className="w-full rounded border px-3 py-2 text-sm"
                                    placeholder="Title"
                                    value={editing.title || ''}
                                    onChange={(e) =>
                                        setEditing({
                                            ...editing,
                                            title: e.target.value,
                                        })
                                    }
                                />
                                <textarea
                                    className="w-full rounded border px-3 py-2 text-sm"
                                    rows={5}
                                    placeholder="Content"
                                    value={editing.content || ''}
                                    onChange={(e) =>
                                        setEditing({
                                            ...editing,
                                            content: e.target.value,
                                        })
                                    }
                                />
                                <input
                                    className="w-full rounded border px-3 py-2 text-sm"
                                    placeholder="Excerpt (optional)"
                                    value={editing.excerpt || ''}
                                    onChange={(e) =>
                                        setEditing({
                                            ...editing,
                                            excerpt: e.target.value,
                                        })
                                    }
                                />
                                <input
                                    className="w-full rounded border px-3 py-2 text-sm"
                                    placeholder="Author name"
                                    value={editing.author_name || ''}
                                    onChange={(e) =>
                                        setEditing({
                                            ...editing,
                                            author_name: e.target.value,
                                        })
                                    }
                                />
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={editing.is_published || false}
                                        onChange={(e) =>
                                            setEditing({
                                                ...editing,
                                                is_published: e.target.checked,
                                            })
                                        }
                                    />
                                    Published
                                </label>
                            </div>
                            <div className="mt-4 flex gap-2">
                                <button
                                    onClick={save}
                                    className="rounded bg-blue-600 px-4 py-2 text-sm text-white"
                                >
                                    Save
                                </button>
                                <button
                                    onClick={() => setEditing(null)}
                                    className="rounded bg-gray-100 px-4 py-2 text-sm"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                )}

                <div className="space-y-3">
                    {posts.data.map((post) => (
                        <div
                            key={post.id}
                            className="rounded-lg border bg-white p-4"
                        >
                            <div className="flex items-start justify-between">
                                <div>
                                    <p className="font-medium">{post.title}</p>
                                    <p className="mt-1 text-xs text-gray-500">
                                        {post.slug} ·{' '}
                                        {post.author_name ?? 'Unknown'}
                                    </p>
                                    {post.excerpt && (
                                        <p className="mt-1 text-sm text-gray-600">
                                            {post.excerpt}
                                        </p>
                                    )}
                                </div>
                                <div className="flex items-center gap-2">
                                    <span
                                        className={`rounded-full px-2 py-0.5 text-xs font-medium ${post.is_published ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}`}
                                    >
                                        {post.is_published
                                            ? 'Published'
                                            : 'Draft'}
                                    </span>
                                    <button
                                        onClick={() => setEditing(post)}
                                        className="text-xs text-blue-600 hover:underline"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        onClick={() => {
                                            if (confirm('Delete?'))
                                                router.delete(
                                                    route(
                                                        'admin.cms.blog.destroy',
                                                        post.id,
                                                    ),
                                                );
                                        }}
                                        className="text-xs text-red-600 hover:underline"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </DashboardLayout>
    );
}
