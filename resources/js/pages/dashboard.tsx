import { Alert, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppHeaderLayout from '@/layouts/app/app-header-layout';
import { SharedData, type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import axios from 'axios';
import { Copy } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

export default function Dashboard() {
    const { server_ip, sftpUser, sftpPassword, siteId, siteUrl, databaseListe } = usePage<SharedData>().props;
    const [copied, setCopied] = useState(false);
    const [openDialogBDD, setOpenDialogBDD] = useState(false);
    const [loadingDialogBDD, setLoadingDialogBDD] = useState(false);

    const [formDataBDD, setFormDataBDD] = useState({
        database_name: '',
        username: '',
        site_id: siteId,
    });

    const handleCopy = () => {
        navigator.clipboard.writeText(server_ip);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000); // réinitialise après 2s
    };

    const handleCreate = async () => {
        setLoadingDialogBDD(true);
        try {
            const res = await axios.post('/api/create-database', formDataBDD);
            const data = res.data
            if (data.success) {
                // setDatabases([...databases, data.credentials]);
                setOpenDialogBDD(false);
                setFormDataBDD({
                    database_name: '',
                    username: '',
                });
            } else {
                alert(data.message);
            }
        } catch (err) {
            alert('Erreur serveur');
        }
        setLoadingDialogBDD(false);
    };

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text);
    };
    return (
        <AppHeaderLayout>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4">
                {/* D'abord faire apparaitre une sorte du popup comme un alerte pour indiquer l'ip */}
                <Alert className="mt-5 flex items-center justify-between">
                    <AlertTitle className="text-lg">
                        L'adresse IP du serveur web vers laquelle votre domaine doit pointer est : <span className="font-mono">{server_ip}</span>
                    </AlertTitle>
                    <Button onClick={handleCopy}>{copied ? 'Copié !' : 'Copier'}</Button>
                </Alert>
                {/* Infromation sftp, et pour aller sur le site */}
                <div className="mt-5 flex w-full flex-row gap-3">
                    {/* Infos SFTP */}
                    <Card className="flex-1">
                        <CardHeader>
                            <CardTitle>Informations SFTP</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-2">
                            <div className="flex items-center justify-between">
                                <span>Hôte :</span>
                                <span className="font-mono">{server_ip || '127.0.0.1'}</span>
                                <Button variant="ghost" size="sm" onClick={() => copyToClipboard(server_ip)}>
                                    <Copy size={16} />
                                </Button>
                            </div>
                            <div className="flex items-center justify-between">
                                <span>Utilisateur :</span>
                                <span className="font-mono">{sftpUser || 'user'}</span>
                                <Button variant="ghost" size="sm" onClick={() => copyToClipboard(sftpUser)}>
                                    <Copy size={16} />
                                </Button>
                            </div>
                            <div className="flex items-center justify-between">
                                <span>Mot de passe :</span>
                                <span className="font-mono">{sftpPassword || '********'}</span>
                                <Button variant="ghost" size="sm" onClick={() => copyToClipboard(sftpPassword || '')}>
                                    <Copy size={16} />
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Lien vers le site */}
                    <Card className="flex-1">
                        <CardHeader>
                            <CardTitle>Accès au site</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <a
                                href={'http://' + siteUrl || '#'}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="break-words text-blue-600 hover:underline"
                            >
                                {siteUrl || 'Site non disponible'}
                            </a>
                        </CardContent>
                    </Card>
                </div>
                {/* Gestion BDD et DNS */}
                <div>
                    <Card>
                        <CardHeader className="flex-row items-center justify-between">
                            <CardTitle>Gestion de la base de donnée</CardTitle>
                            <Button onClick={() => setOpenDialogBDD(true)}>Crée une base de donnée</Button>
                        </CardHeader>
                        <CardContent>
                            {databaseListe.length === 0 ? (
                                <p className="text-sm text-gray-500">Aucune base de données créée pour le moment.</p>
                            ) : (
                                <div className="space-y-2">
                                    {databaseListe.map((db, index) => (
                                        <div key={index} className="space-y-1 rounded-lg border p-3">
                                            <div className="flex justify-between">
                                                <span>Base :</span>
                                                <span className="font-mono">{db.database_name}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Utilisateur :</span>
                                                <span className="font-mono">{db.username}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Mot de passe :</span>
                                                <span className="font-mono">{db.password}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Hôte :</span>
                                                <span className="font-mono">{db.host}</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
            <Dialog open={openDialogBDD} onOpenChange={setOpenDialogBDD}>
                {/* <DialogTrigger asChild>
                    <Button>Créer une base</Button>
                </DialogTrigger> */}
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Créer une nouvelle base</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3">
                        <Input
                            placeholder="Nom de la base"
                            value={formDataBDD.database_name}
                            onChange={(e) => setFormDataBDD((prev) => ({ ...prev, database_name: e.target.value }))}
                        />
                        <Input
                            placeholder="Utilisateur"
                            value={formDataBDD.username}
                            onChange={(e) => setFormDataBDD((prev) => ({ ...prev, username: e.target.value }))}
                        />
                        <Button onClick={handleCreate} disabled={loadingDialogBDD}>
                            {loadingDialogBDD ? 'Création...' : 'Créer'}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </AppHeaderLayout>
    );
}

{
    /* <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </AppLayout> */
}
