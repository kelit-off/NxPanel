import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from "@/components/ui/input";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, usePage } from "@inertiajs/react";
import axios from "axios";
import { useState } from "react";

export default function LandingPage() {
    const { clientCount, serverListe, siteCount } = usePage().props;
    const [openDialogServer, setOpenDialogServer] = useState(false)
    const [loadingDialogServer, setLoadingDialogServer] = useState(false)
    const [formDataServer, setFormDataServer ] = useState({
        hostname: "",
        username: "",
        password: "",
        ip: "",
        port: ""
    })
    const cardCSS = "border-2 rounded-lg p-4 h-30 mx-32 text-center";

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Administration',
            href: '/admin',
        },
    ];

    const handleCreate = async () => {
        setLoadingDialogServer(true);
        try {
            const res = await axios.post('/api/add-server', formDataServer);
            const data = res.data;
            if (res.status == 200) {
                window.location.reload()
                setOpenDialogServer(false);
                setFormDataServer({
                    hostname: '',
                    username: '',
                    password: '',
                    ip: '',
                    port: '',
                });
            } else {
                alert(data.message);
            }
        } catch (err) {
            alert('Erreur serveur');
        }
        setLoadingDialogServer(false);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel Admin" />
            <div className="flex h-full flex-1 flex-col gap-6">
                <div className="flex flex-row gap-4 mt-4">
                    <Card>
                        <CardHeader>
                            <CardTitle>Utilisateur inscrit</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {clientCount}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Nombre de serveur</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {serverListe.length}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle>Nombre de site</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {siteCount}
                        </CardContent>
                    </Card>
                </div>
                <div>
                    <Card>
                        <CardHeader className="flex-row items-center justify-between">
                            <CardTitle>Liste des serveurs</CardTitle>
                            <Button onClick={() => setOpenDialogServer(true)}>Ajouter un nouveau server</Button>
                        </CardHeader>
                        <CardContent>
                            {serverListe.length === 0 ? (
                                <p className="text-sm text-gray-500">Aucune base de données créée pour le moment.</p>
                            ) : (
                                <div className="space-y-2">
                                    {serverListe.map((server, index) => (
                                        <div key={index} className="space-y-1 rounded-lg border p-3">
                                            <div className="flex justify-between">
                                                <span>Hostname :</span>
                                                <span className="font-mono">{server.hostname}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Utilisateur :</span>
                                                <span className="font-mono">{server.username}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Ip</span>
                                                <span className="font-mono">{server.ip}</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
            <Dialog open={openDialogServer} onOpenChange={setOpenDialogServer}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Créer une nouvelle base</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-3">
                        <Input
                            placeholder="HostName"
                            value={formDataServer.hostname}
                            onChange={(e) => setFormDataServer((prev) => ({ ...prev, hostname: e.target.value }))}
                        />
                        <Input
                            placeholder="Utilisateur"
                            value={formDataServer.username}
                            onChange={(e) => setFormDataServer((prev) => ({ ...prev, username: e.target.value }))}
                        />
                        <Input 
                            placeholder="SSH Key"
                            value={formDataServer.password}
                            onChange={(e) => setFormDataServer((prev) => ({ ...prev, password: e.target.value }))}
                        />
                        <Input 
                            placeholder="Ip"
                            value={formDataServer.ip}
                            onChange={(e) => setFormDataServer((prev) => ({ ...prev, ip: e.target.value }))}
                        />
                        <Input 
                            placeholder="Port"
                            value={formDataServer.port}
                            onChange={(e) => setFormDataServer((prev) => ({ ...prev, port: e.target.value }))}
                        />
                        <Button onClick={handleCreate} disabled={loadingDialogServer}>
                            {loadingDialogServer ? 'Ajout...' : 'Ajouter'}
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    )
}